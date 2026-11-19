<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Digest – NIMR GMU</title>
<style>
  body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
  .wrapper { max-width:660px; margin:30px auto; background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1); }
  .header  { background:#2664EB; color:#fff; padding:24px 32px; }
  .header h1 { margin:0; font-size:20px; }
  .header p  { margin:6px 0 0; font-size:13px; opacity:.85; }
  .body    { padding:28px 32px; }
  .greeting { font-size:16px; margin-bottom:18px; }
  .stats-grid { display:flex; gap:12px; margin-bottom:28px; flex-wrap:wrap; }
  .stat-card  { flex:1; min-width:120px; background:#f8f9fa; border-radius:6px; padding:14px 16px; text-align:center; border-top:3px solid #2664EB; }
  .stat-card.danger  { border-color:#c0392b; }
  .stat-card.warning { border-color:#f39c12; }
  .stat-card.success { border-color:#27ae60; }
  .stat-card .num { font-size:28px; font-weight:700; color:#2664EB; }
  .stat-card.danger  .num { color:#c0392b; }
  .stat-card.warning .num { color:#f39c12; }
  .stat-card.success .num { color:#27ae60; }
  .stat-card .lbl { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#888; margin-top:4px; }
  .section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#888; margin:24px 0 10px; }
  table    { width:100%; border-collapse:collapse; margin-bottom:16px; }
  th       { background:#f0f0f0; text-align:left; padding:8px 10px; font-size:13px; color:#555; }
  td       { padding:9px 10px; font-size:13px; border-bottom:1px solid #eee; vertical-align:top; }
  .badge   { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; }
  .badge-report     { background:#cce5ff; color:#004085; }
  .badge-compliance { background:#d4edda; color:#155724; }
  .badge-ethics     { background:#fff3cd; color:#856404; }
  .overdue-label { color:#c0392b; font-weight:600; }
  .none-msg { color:#aaa; font-style:italic; font-size:13px; padding:8px 0; }
  .cta     { margin:24px 0 0; text-align:center; }
  .cta a   { background:#2664EB; color:#fff; text-decoration:none; padding:11px 28px; border-radius:4px; font-size:14px; }
  .footer  { background:#f8f8f8; padding:16px 32px; font-size:12px; color:#888; border-top:1px solid #eee; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>NIMR Grants Management Unit</h1>
    <p>Weekly Digest &mdash; week of {{ now()->startOfWeek()->format('d M Y') }}</p>
  </div>

  <div class="body">
    <p class="greeting">Dear {{ $recipientName }},</p>

    {{-- Stats grid --}}
    <div class="stats-grid">
      <div class="stat-card success">
        <div class="num">{{ $stats['active_awards'] }}</div>
        <div class="lbl">Active Awards</div>
      </div>
      <div class="stat-card">
        <div class="num">{{ $stats['open_calls'] }}</div>
        <div class="lbl">Open Calls</div>
      </div>
      <div class="stat-card {{ $stats['proposals_pending_review'] > 0 ? 'warning' : '' }}">
        <div class="num">{{ $stats['proposals_pending_review'] }}</div>
        <div class="lbl">Proposals Pending Review</div>
      </div>
      <div class="stat-card {{ $stats['overdue_reports'] > 0 ? 'danger' : 'success' }}">
        <div class="num">{{ $stats['overdue_reports'] }}</div>
        <div class="lbl">Overdue Reports</div>
      </div>
    </div>

    {{-- The week's movement — slide 15 of the GMU concept --}}
    @if(count($thisWeek ?? []))
      @if(count($thisWeek['new_calls'] ?? []))
        <div class="section-title">New Calls This Week</div>
        <table>
          <thead><tr><th>Call</th><th>Donor</th><th>Deadline</th></tr></thead>
          <tbody>
          @foreach($thisWeek['new_calls'] as $call)
            <tr>
              <td>{{ $call['title'] }}</td>
              <td>{{ $call['donor'] }}</td>
              <td>{{ $call['due']?->format('d M Y') }} ({{ $call['days'] }} days)</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif

      @if(count($thisWeek['closing_soon'] ?? []))
        <div class="section-title">Calls Closing Within 30 Days</div>
        <table>
          <thead><tr><th>Call</th><th>Donor</th><th>Closes</th></tr></thead>
          <tbody>
          @foreach($thisWeek['closing_soon'] as $call)
            <tr>
              <td>{{ $call['title'] }}</td>
              <td>{{ $call['donor'] }}</td>
              <td>{{ $call['due']?->format('d M Y') }} ({{ $call['days'] }} days)</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif

      @if(count($thisWeek['submissions'] ?? []))
        <div class="section-title">Proposals Submitted This Week</div>
        <table>
          <thead><tr><th>Proposal</th><th>Lead PI</th><th>Donor</th></tr></thead>
          <tbody>
          @foreach($thisWeek['submissions'] as $submission)
            <tr><td>{{ $submission['title'] }}</td><td>{{ $submission['pi'] }}</td><td>{{ $submission['donor'] }}</td></tr>
          @endforeach
          </tbody>
        </table>
      @endif

      @if(count($thisWeek['new_awards'] ?? []))
        <div class="section-title">New Awards This Week</div>
        <table>
          <thead><tr><th>Award</th><th>Donor</th><th>Value</th></tr></thead>
          <tbody>
          @foreach($thisWeek['new_awards'] as $award)
            <tr><td>{{ $award['title'] }}</td><td>{{ $award['donor'] }}</td><td>{{ $award['amount'] }}</td></tr>
          @endforeach
          </tbody>
        </table>
      @endif

      @if(count($thisWeek['awaiting_closeout'] ?? []))
        <div class="section-title">Awards Awaiting Closeout</div>
        <table>
          <thead><tr><th>Award</th><th>Ended</th><th>Days since</th></tr></thead>
          <tbody>
          @foreach($thisWeek['awaiting_closeout'] as $award)
            <tr><td>{{ $award['title'] }}</td><td>{{ $award['ended']?->format('d M Y') }}</td><td>{{ $award['days'] }}</td></tr>
          @endforeach
          </tbody>
        </table>
      @endif
    @endif

    {{-- Overdue items --}}
    <div class="section-title">Overdue Items</div>
    @if(count($overdueItems))
    <table>
      <thead>
        <tr><th>Type</th><th>Item</th><th>Award / Project</th><th>Days Overdue</th></tr>
      </thead>
      <tbody>
        @foreach($overdueItems as $item)
        <tr>
          <td><span class="badge badge-{{ $item['type'] }}">{{ ucfirst($item['type']) }}</span></td>
          <td>{{ $item['label'] }}</td>
          <td>{{ $item['award_title'] }}</td>
          <td class="overdue-label">{{ $item['overdue_days'] }} days</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <p class="none-msg">No overdue items. Keep up the good work!</p>
    @endif

    {{-- Upcoming (next 14 days) --}}
    <div class="section-title">Upcoming (Next 14 Days)</div>
    @if(count($upcoming))
    <table>
      <thead>
        <tr><th>Type</th><th>Item</th><th>Award / Project</th><th>Due Date</th><th>In</th></tr>
      </thead>
      <tbody>
        @foreach($upcoming as $item)
        <tr>
          <td><span class="badge badge-{{ $item['type'] }}">{{ ucfirst($item['type']) }}</span></td>
          <td>{{ $item['label'] }}</td>
          <td>{{ $item['award_title'] }}</td>
          <td>{{ \Carbon\Carbon::parse($item['due'])->format('d M Y') }}</td>
          <td>{{ $item['days'] }}d</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <p class="none-msg">No deadlines in the next 14 days.</p>
    @endif

    <div class="cta">
      <a href="{{ url('/') }}">Open GMU Portal</a>
    </div>
  </div>

  <div class="footer">
    This is an automated weekly summary from the NIMR Grants Management Unit system. Please do not reply to this email.
  </div>
</div>
</body>
</html>
