<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->enum('category', [
                'capabilities_statement',
                'facilities_equipment',
                'budget_justification',
                'data_management',
                'ethical_framework',
                'institutional_background',
                'cv_biosketch',
                'other',
            ]);
            $table->text('content');           // the actual reusable text block
            $table->text('usage_notes')->nullable(); // guidance on when/how to use it
            $table->json('tags')->nullable();  // searchable keywords
            $table->string('funder_relevance')->nullable(); // 'NIH,USAID,Wellcome' etc.
            $table->boolean('is_approved')->default(false); // coordinator-approved for use
            $table->unsignedSmallInteger('use_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // MySQL FULLTEXT for search. SQLite (used by the test suite) has no
            // equivalent, so the index is created only where it is supported.
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['title', 'content']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_items');
    }
};
