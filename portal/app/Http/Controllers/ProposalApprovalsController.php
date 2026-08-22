<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalApproval;
use App\Services\Notifier;
use Illuminate\Http\Request;

/**
 * Institutional sign-off between internal review and submission to the donor
 * (audit finding M9).
 *
 * The GMU consolidates the three internal reviews; the Directorate of Research
 * Coordination & Promotion clears the proposal institutionally; the Director
 * General gives the final approval and the Letter of Support is issued. Only
 * then does the PI submit to the donor.
 */
class ProposalApprovalsController extends Controller
{
    public function store(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'level'                    => 'required|in:drcp,dg',
            'status'                   => 'required|in:approved,returned',
            'comments'                 => 'nullable|string|max:2000',
            'letter_of_support_issued' => 'nullable|boolean',
        ]);

        $this->authorize('approveAt', [$proposal, $data['level']]);

        // The chain runs in order: the DG signs after the DRCP, not before.
        if ($data['level'] === 'dg' && ! $proposal->hasApprovalAt('drcp')) {
            return back()->with('error', 'The DRCP has not cleared this proposal yet.');
        }

        $approval = ProposalApproval::updateOrCreate(
            ['proposal_id' => $proposal->id, 'level' => $data['level']],
            [
                'status'                   => $data['status'],
                'decided_by'               => auth()->id(),
                'decided_at'               => now(),
                'comments'                 => $data['comments'] ?? null,
                'letter_of_support_issued' => $request->boolean('letter_of_support_issued'),
            ]
        );

        $notifier = app(Notifier::class);

        if ($data['status'] === 'returned') {
            // Sent back to the team to address the comments.
            $previous = $proposal->status;
            $proposal->update(['status' => 'under_development']);
            $notifier->proposalStatusChanged($proposal->fresh(), $previous, 'under_development');
        } elseif ($data['level'] === 'dg') {
            // Fully cleared: ready to go to the donor.
            $previous = $proposal->status;
            $proposal->update(['status' => 'approved']);

            if ($proposal->status !== $previous) {
                $notifier->proposalStatusChanged($proposal->fresh(), $previous, 'approved');
            }

            $notifier->send(
                array_filter([$proposal->leadPi, $proposal->assignedOfficer]),
                'proposal_cleared',
                'Cleared for submission: ' . $proposal->title,
                'The Director General has approved this proposal'
                    . ($approval->letter_of_support_issued ? ' and the Letter of Support has been issued.' : '.')
                    . ' It may now be submitted to the donor.',
                $proposal
            );
        } else {
            $notifier->send(
                array_filter([$proposal->leadPi, $proposal->assignedOfficer]),
                'proposal_status',
                'DRCP clearance recorded',
                sprintf('"%s" has been cleared by the DRCP and is now with the Director General.', $proposal->title),
                $proposal
            );
        }

        return back()->with('success', ProposalApproval::LEVELS[$data['level']] . ' decision recorded.');
    }
}
