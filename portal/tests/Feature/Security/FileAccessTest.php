<?php

namespace Tests\Feature\Security;

use App\Models\Document;
use App\Support\GrantFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit findings C2 and C6 — what may be uploaded, where it lands, and who may
 * fetch it back.
 */
class FileAccessTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(GrantFiles::DISK);
    }

    public function test_executable_uploads_are_rejected(): void
    {
        $coordinator = $this->user('coordinator');

        foreach (['payload.php', 'payload.phtml', 'shell.phar', 'page.html', 'trick.pdf.php'] as $name) {
            $this->actingAs($coordinator)
                ->post(route('documents.store'), [
                    'title'    => 'probe ' . $name,
                    'category' => 'other',
                    'version'  => 1,
                    'file'     => UploadedFile::fake()->createWithContent($name, '<?php echo "x";'),
                ])
                ->assertSessionHasErrors('file');
        }

        $this->assertSame(0, Document::count());
    }

    public function test_uploads_land_on_the_private_disk_not_the_public_one(): void
    {
        $coordinator = $this->user('coordinator');

        $this->actingAs($coordinator)->post(route('documents.store'), [
            'title'    => 'Signed contract scan',
            'category' => 'contract',
            'version'  => 1,
            'file'     => UploadedFile::fake()->create('contract.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $document = Document::first();

        $this->assertNotNull($document);
        Storage::disk(GrantFiles::DISK)->assertExists($document->file_path);
        $this->assertStringNotContainsString('public', $document->file_path);
    }

    public function test_a_private_document_is_not_downloadable_by_other_researchers(): void
    {
        $uploader = $this->user('coordinator');
        $outsider = $this->user('pi');

        $this->actingAs($uploader)->post(route('documents.store'), [
            'title'    => 'Board minutes',
            'category' => 'other',
            'version'  => 1,
            'file'     => UploadedFile::fake()->create('minutes.pdf', 20, 'application/pdf'),
        ]);

        $document = Document::first();

        $this->actingAs($outsider)->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs($uploader)->get(route('documents.show', $document))->assertOk();
    }

    public function test_a_document_marked_public_is_downloadable_by_any_member_of_staff(): void
    {
        $uploader = $this->user('coordinator');

        $this->actingAs($uploader)->post(route('documents.store'), [
            'title'     => 'Proposal template',
            'category'  => 'template',
            'version'   => 1,
            'is_public' => 1,
            'file'      => UploadedFile::fake()->create('template.docx', 20),
        ]);

        $this->actingAs($this->user('pi'))
            ->get(route('documents.show', Document::first()))
            ->assertOk();
    }

    public function test_a_signed_contract_is_only_downloadable_by_the_award_team(): void
    {
        $coordinator = $this->user('coordinator');
        $pi          = $this->user('pi');
        $outsider    = $this->user('pi');
        $award       = $this->award($this->proposal($pi));

        $award->update([
            'signed_contract' => GrantFiles::store(
                UploadedFile::fake()->create('contract.pdf', 30, 'application/pdf'),
                'award-contracts'
            ),
        ]);

        $this->actingAs($outsider)->get(route('awards.contract', $award))->assertForbidden();
        $this->actingAs($pi)->get(route('awards.contract', $award))->assertOk();
        $this->actingAs($coordinator)->get(route('awards.contract', $award))->assertOk();
    }

    public function test_replacing_a_file_removes_the_previous_one(): void
    {
        $first  = GrantFiles::store(UploadedFile::fake()->create('v1.pdf', 10, 'application/pdf'), 'reports');
        $second = GrantFiles::replace(UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'), 'reports', $first);

        Storage::disk(GrantFiles::DISK)->assertMissing($first);
        Storage::disk(GrantFiles::DISK)->assertExists($second);
    }
}
