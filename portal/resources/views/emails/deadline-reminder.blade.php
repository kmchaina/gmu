<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deadline Reminder – NIMR GMU</title>
<style>
  body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
  .wrapper { max-width:640px; margin:30px auto; background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1); }
  .header  { background:#2664EB; color:#fff; padding:24px 32px; }
  .header h1 { margin:0; font-size:20px; }
  .header p  { margin:4px 0 0; font-size:13px; opacity:.85; }
  .body    { padding:28px 32px; }
  .greeting { font-size:16px; margin-bottom:20px; }
  .intro   { color:#444; margin-bottom:24px; line-height:1.6; }
  .section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#888; margin:24px 0 10px; }
  table    { width:100%; border-collapse:collapse; margin-bottom:16px; }
  th       { background:#f0f0f0; text-align:left; padding:8px 10px; font-size:13px; color:#555; }
  td       { padding:9px 10px; font-size:14px; border-bottom:1px solid #eee; vertical-align:top; }
  .badge   { display:inline-block; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:600; }
  .badge-report     { background:#cce5ff; color:#004085; }
  .badge-compliance { background:#d4edda; color:#155724; }
  .badge-ethics     { background:#fff3cd; color:#856404; }
  .days-col { white-space:nowrap; font-weight:600; }
  .urgent  { color:#c0392b; }
  .footer  { background:#f8f8f8; padding:16px 32px; font-size:12px; color:#888; border-top:1px solid #eee; }
  .cta     { margin:24px 0 0; text-align:center; }
  .cta a   { background:#2664EB; color:#fff; text-decoration:none; padding:11px 28px; border-radius:4px; font-size:14px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>NIMR Grants Management Unit</h1>
    <p>Deadline Reminder – {{ $daysWindow }}-day notice</p>
  </div>

  <div class="body">
    <p class="greeting">Dear {{ $recipientName }},</p>
    <p class="intro">
      The following grant obligations are due within the next <strong>{{ $daysWindow }} day{{ $daysWindow === 1 ? '' : 's' }}</strong>.
      Please take the necessary action before the deadlines to maintain compliance.
    </p>

    @php
      $reports     = collect($reminders)->where('type', 'report');
      $compliance  = collect($reminders)->where('type', 'compliance');
      $ethics      = collect($reminders)->where('type', 'ethics');
    @endphp

    {{-- Reports --}}
    @if($reports->isNotEmpty())
    <div class="section-title">Progress Reports</div>
    <table>
      <thead>
        <tr>
          <th>Award / Project</th>
          <th>Report</th>
          <th>Due Date</th>
          <th>Days Left</th>
        </tr>
      </thead>
      <tbody>
        @foreach($reports as $r)
        <tr>
          <td>{{ $r['award_title'] }}</td>
          <td>
            <span class="badge badge-report">Report</span>
            {{ $r['label'] }}
          </td>
          <td>{{ \Carbon\Carbon::parse($r['due'])->format('d M Y') }}</td>
          <td class="days-col {{ $r['days'] <= 7 ? 'urgent' : '' }}">{{ $r['days'] }} days</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    {{-- Compliance Calendar --}}
    @if($compliance->isNotEmpty())
    <div class="section-title">Compliance Events</div>
    <table>
      <thead>
        <tr>
          <th>Award / Project</th>
          <th>Deliverable</th>
          <th>Due Date</th>
          <th>Days Left</th>
        </tr>
      </thead>
      <tbody>
        @foreach($compliance as $r)
        <tr>
          <td>{{ $r['award_title'] }}</td>
          <td>
            <span class="badge badge-compliance">Compliance</span>
            {{ $r['label'] }}
          </td>
          <td>{{ \Carbon\Carbon::parse($r['due'])->format('d M Y') }}</td>
          <td class="days-col {{ $r['days'] <= 7 ? 'urgent' : '' }}">{{ $r['days'] }} days</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    {{-- Ethics Renewals --}}
    @if($ethics->isNotEmpty())
    <div class="section-title">Ethics / Protocol Renewals</div>
    <table>
      <thead>
        <tr>
          <th>Award / Project</th>
          <th>Protocol</th>
          <th>Expiry Date</th>
          <th>Days Left</th>
        </tr>
      </thead>
      <tbody>
        @foreach($ethics as $r)
        <tr>
          <td>{{ $r['award_title'] }}</td>
          <td>
            <span class="badge badge-ethics">Ethics</span>
            {{ $r['label'] }}
          </td>
          <td>{{ \Carbon\Carbon::parse($r['due'])->format('d M Y') }}</td>
          <td class="days-col {{ $r['days'] <= 14 ? 'urgent' : '' }}">{{ $r['days'] }} days</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <div class="cta">
      <a href="{{ url('/awards') }}">View My Awards in GMU Portal</a>
    </div>
  </div>

  <div class="footer">
    This is an automated message from the NIMR Grants Management Unit system. Please do not reply to this email.
    For assistance contact the GMU Coordinator.
  </div>
</div>
</body>
</html>
