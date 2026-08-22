<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CallsController;
use App\Http\Controllers\DonorsController;
use App\Http\Controllers\ProposalApprovalsController;
use App\Http\Controllers\ProposalDocumentsController;
use App\Http\Controllers\ProposalTeamController;
use App\Http\Controllers\ProposalsController;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\AwardBudgetsController;
use App\Http\Controllers\ComplianceCalendarController;
use App\Http\Controllers\MonitoringVisitsController;
use App\Http\Controllers\EthicsRenewalsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\DonorPoliciesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ResearcherProfileController;
use App\Http\Controllers\LibraryItemsController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard',  [DashboardController::class,  'index'])->name('dashboard');
    Route::get('/analytics',  [AnalyticsController::class,  'index'])->name('analytics')
        ->middleware('role:coordinator,planning_officer,finance_officer');

    Route::resource('calls', CallsController::class);
    Route::get('calls/{call}/document',       [CallsController::class, 'downloadDocument'])->name('calls.document');
    Route::post('calls/{call}/interest',     [CallsController::class, 'expressInterest'])->name('calls.interest');
    Route::post('calls/{call}/select-team',  [CallsController::class, 'selectTeam'])->name('calls.select-team');
    Route::resource('proposals', ProposalsController::class);
    Route::post('proposals/{proposal}/review', [ProposalsController::class, 'submitReview'])->name('proposals.review');
    Route::post('proposals/{proposal}/approval', [ProposalApprovalsController::class, 'store'])->name('proposals.approval');

    // Proposal artefacts and team — the pre-award records that had models and
    // tables but no way to create them (finding M7).
    Route::scopeBindings()->group(function () {
        Route::post('proposals/{proposal}/documents',                [ProposalDocumentsController::class, 'store'])->name('proposals.documents.store');
        Route::get('proposals/{proposal}/documents/{document}',      [ProposalDocumentsController::class, 'download'])->name('proposals.documents.download');
        Route::delete('proposals/{proposal}/documents/{document}',   [ProposalDocumentsController::class, 'destroy'])->name('proposals.documents.destroy');

        Route::post('proposals/{proposal}/team',                     [ProposalTeamController::class, 'store'])->name('proposals.team.store');
        Route::delete('proposals/{proposal}/team/{member}',          [ProposalTeamController::class, 'destroy'])->name('proposals.team.destroy');
    });
    Route::resource('awards', AwardsController::class);
    Route::get('awards/{award}/contract',     [AwardsController::class, 'downloadContract'])->name('awards.contract');

    // Award sub-resources. scopeBindings() makes Laravel resolve each child
    // through its parent award, so a budget line belonging to another award
    // cannot be reached through this one (finding H5).
    Route::scopeBindings()->group(function () {
        Route::post('awards/{award}/budgets',              [AwardBudgetsController::class, 'store'])->name('awards.budgets.store');
        Route::delete('awards/{award}/budgets/{budget}',   [AwardBudgetsController::class, 'destroy'])->name('awards.budgets.destroy');

        Route::post('awards/{award}/compliance',           [ComplianceCalendarController::class, 'store'])->name('awards.compliance.store');
        Route::patch('awards/{award}/compliance/{item}',   [ComplianceCalendarController::class, 'update'])->name('awards.compliance.update');
        Route::delete('awards/{award}/compliance/{item}',  [ComplianceCalendarController::class, 'destroy'])->name('awards.compliance.destroy');

        Route::post('awards/{award}/monitoring',           [MonitoringVisitsController::class, 'store'])->name('awards.monitoring.store');
        Route::get('awards/{award}/monitoring/{visit}/report', [MonitoringVisitsController::class, 'downloadReport'])->name('awards.monitoring.report');
        Route::delete('awards/{award}/monitoring/{visit}', [MonitoringVisitsController::class, 'destroy'])->name('awards.monitoring.destroy');

        Route::post('awards/{award}/ethics',               [EthicsRenewalsController::class, 'store'])->name('awards.ethics.store');
        Route::patch('awards/{award}/ethics/{renewal}',    [EthicsRenewalsController::class, 'update'])->name('awards.ethics.update');
        Route::get('awards/{award}/ethics/{renewal}/certificate', [EthicsRenewalsController::class, 'downloadCertificate'])->name('awards.ethics.certificate');
        Route::delete('awards/{award}/ethics/{renewal}',   [EthicsRenewalsController::class, 'destroy'])->name('awards.ethics.destroy');
    });

    Route::resource('reports', ReportsController::class);
    Route::get('reports/{report}/file',       [ReportsController::class, 'download'])->name('reports.download');
    Route::resource('documents', DocumentsController::class);
    Route::resource('donor-policies', DonorPoliciesController::class);
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');

    Route::middleware(['role:coordinator,planning_officer'])->group(function () {
        Route::resource('donors', DonorsController::class);
    });

    Route::middleware(['role:coordinator'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UsersController::class);
        Route::get('activity-log',       [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('activity-log/{log}', [ActivityLogController::class, 'show'])->name('activity-log.show');
    });

    // AI Intelligence. Finding M14: these endpoints call a paid API, so they
    // are rate limited per user as well as role-gated.
    Route::middleware(['role:coordinator,planning_officer,finance_officer', 'throttle:20,1'])->group(function () {
        Route::get('ai',                        [AiController::class, 'hub'])->name('ai.hub');
        Route::get('ai/matching',               [AiController::class, 'matchingIndex'])->name('ai.matching');
        Route::get('ai/matching/{call}',        [AiController::class, 'matchCall'])->name('ai.match-call');
        Route::get('ai/risk',                   [AiController::class, 'riskIndex'])->name('ai.risk');
        Route::get('ai/risk/{award}',           [AiController::class, 'riskAnalysis'])->name('ai.risk-analysis');
    });
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('ai/report-quality/{report}', [AiController::class, 'reportQuality'])->name('ai.report-quality');
        Route::post('ai/draft-section',           [AiController::class, 'draftSection'])->name('ai.draft-section');
    });

    // Proposal Library
    Route::resource('library', LibraryItemsController::class);
    Route::post('library/{library}/approve', [LibraryItemsController::class, 'toggleApproval'])->name('library.approve');
    Route::get('library/{library}/copy',     [LibraryItemsController::class, 'copy'])->name('library.copy');

    // Researcher profiles & FCOI
    Route::get('researchers',               [ResearcherProfileController::class, 'index'])->name('researchers.index');
    Route::get('researchers/{user}',        [ResearcherProfileController::class, 'show'])->name('researchers.show');
    Route::get('researchers/{user}/edit',   [ResearcherProfileController::class, 'edit'])->name('researchers.edit');
    Route::put('researchers/{user}',        [ResearcherProfileController::class, 'update'])->name('researchers.update');
    Route::post('researchers/{user}/fcoi',  [ResearcherProfileController::class, 'storeFcoi'])->name('researchers.fcoi.store');
    Route::patch('fcoi/{disclosure}/review',[ResearcherProfileController::class, 'reviewFcoi'])->name('fcoi.review');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
