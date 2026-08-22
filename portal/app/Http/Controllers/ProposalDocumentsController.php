<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Support\GrantFiles;
use App\Support\UploadRules;
use Illuminate\Http\Request;

/**
 * Concept notes, full proposals, budgets, letters of support and ethics
 * paperwork — the artefacts of the pre-award process (audit finding M7).
 *
 * The table, model and relationship existed and the proposal page eagerly
 * loaded documents it never displayed; there was no route, controller or
 * interface, so nothing could be attached to a proposal at all.
 */
class ProposalDocumentsController extends Controller
{
    public const TYPES = [
        'concept_note'       => 'Concept note',
        'full_proposal'      => 'Full proposal',
        'budget'             => 'Budget',
        'budget_narrative'   => 'Budget narrative',
        'letter_of_support'  => 'Letter of support',
        'ethics'             => 'Ethics / protocol',
        'cv'                 => 'CV / biosketch',
        'donor_form'         => 'Donor form',
        'other'              => 'Other',
    ];

    public function store(Request $request, Proposal $proposal)
    {
        $this->authorize('manageDocuments', $proposal);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'document_type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'file'          => UploadRules::repositoryRequired(),
        ]);

        $file = $request->file('file');

        // Version within this proposal and document type, so revisions of the
        // same artefact read as v1, v2, v3 rather than all looking current.
        $version = $proposal->documents()
            ->where('document_type', $data['document_type'])
            ->max('version') + 1;

        $proposal->documents()->create([
            'uploaded_by'   => auth()->id(),
            'document_type' => $data['document_type'],
            'title'         => $data['title'],
            'file_path'     => GrantFiles::store($file, 'proposal-documents'),
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'version'       => min($version, 127),
        ]);

        return back()->with('success', 'Document attached to the proposal.');
    }

    public function download(Proposal $proposal, ProposalDocument $document)
    {
        $this->authorize('view', $proposal);

        return GrantFiles::download($document->file_path, $document->file_name);
    }

    public function destroy(Proposal $proposal, ProposalDocument $document)
    {
        $this->authorize('manageDocuments', $proposal);

        GrantFiles::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
