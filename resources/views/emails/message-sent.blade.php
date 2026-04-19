<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 32px 16px; }
        .card { background: #fff; border-radius: 8px; max-width: 560px; margin: 0 auto; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: #7c3aed; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 700; }
        .header p { color: #ddd6fe; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 28px 32px; }
        .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; letter-spacing: .05em; margin-bottom: 4px; }
        .value { font-size: 14px; color: #111827; margin-bottom: 20px; }
        .message-body { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 16px; font-size: 14px; color: #374151; line-height: 1.6; }
        .meta { display: flex; gap: 24px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .meta-item { flex: 1; }
        .meta-item .num { font-size: 22px; font-weight: 700; color: #7c3aed; }
        .meta-item .lbl { font-size: 12px; color: #6b7280; }
        .footer { background: #f9fafb; padding: 16px 32px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>TMM &mdash; SMS Message Sent</h1>
            <p>This is a demo notification. No real SMS was delivered.</p>
        </div>
        <div class="body">
            <div class="label">Campaign Name</div>
            <div class="value">{{ $smsMessage->name }}</div>

            <div class="label">Message Body</div>
            <div class="message-body">{{ $smsMessage->body }}</div>

            <div class="meta">
                <div class="meta-item">
                    <div class="num">{{ number_format($smsMessage->recipient_count) }}</div>
                    <div class="lbl">Recipients</div>
                </div>
                <div class="meta-item">
                    <div class="num">{{ number_format($smsMessage->credits_used) }}</div>
                    <div class="lbl">Credits Used</div>
                </div>
                <div class="meta-item">
                    <div class="num">{{ $smsMessage->sent_at?->format('H:i') }}</div>
                    <div class="lbl">Sent At</div>
                </div>
            </div>
        </div>
        <div class="footer">
            Text Message Management &bull; Demo Environment &bull; Powered by Mailhog
        </div>
    </div>
</body>
</html>
