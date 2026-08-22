<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit finding C5 — one deletion could erase the institutional grant record.
 *
 * Deleting a user cascaded into their proposals, and from there into awards,
 * budgets, reports, compliance items, ethics renewals and monitoring visits;
 * it also removed every funding call they had posted (and everything hanging
 * off those) plus every document and donor policy they had uploaded. Deleting
 * a donor did the same through its calls and awards.
 *
 * Ownership and reference links are now RESTRICT: the parent cannot be deleted
 * while records point at it. Genuinely dependent children — a proposal's own
 * reviews, an award's own budget lines — keep ON DELETE CASCADE, since they
 * are meaningless without their parent and both parents use soft deletes.
 *
 * SQLite cannot alter foreign keys, so this is applied on MySQL/MariaDB only;
 * the application-level protections (soft-deleted users, no delete route) hold
 * on every driver and are what the test suite exercises.
 */
return new class extends Migration
{
    /**
     * table => [column => referenced table]
     */
    private array $restrict = [
        // Records that must outlive the person who entered them
        'proposals'          => ['lead_pi_id'   => 'users'],
        'funding_calls'      => ['posted_by'    => 'users', 'donor_id' => 'donors'],
        'documents'          => ['uploaded_by'  => 'users'],
        'donor_policies'     => ['uploaded_by'  => 'users', 'donor_id' => 'donors'],
        'proposal_documents' => ['uploaded_by'  => 'users'],
        'proposal_reviews'   => ['reviewer_id'  => 'users'],
        'monitoring_visits'  => ['conducted_by' => 'users'],
        'library_items'      => ['created_by'   => 'users'],
        'fcoi_disclosures'   => ['user_id'      => 'users'],
        // An award must not vanish with its proposal, nor a proposal with its call
        'awards'             => ['proposal_id'  => 'proposals', 'donor_id' => 'donors'],
    ];

    public function up(): void
    {
        if (! $this->supported()) {
            return;
        }

        foreach ($this->restrict as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => $references) {
                    $blueprint->dropForeign([$column]);
                    $blueprint->foreign($column)->references('id')->on($references)->restrictOnDelete();
                }
            });
        }

        // proposals.funding_call_id is handled separately: it is part of a
        // composite lookup path and needs the same protection.
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['funding_call_id']);
            $table->foreign('funding_call_id')->references('id')->on('funding_calls')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! $this->supported()) {
            return;
        }

        foreach ($this->restrict as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => $references) {
                    $blueprint->dropForeign([$column]);
                    $blueprint->foreign($column)->references('id')->on($references)->cascadeOnDelete();
                }
            });
        }

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['funding_call_id']);
            $table->foreign('funding_call_id')->references('id')->on('funding_calls')->cascadeOnDelete();
        });
    }

    private function supported(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
