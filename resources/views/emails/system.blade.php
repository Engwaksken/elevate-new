@php($brandName = config('app.name', 'ElevateHer360'))
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1ee;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ee;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #eadede;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#800000;padding:20px 26px;color:#ffffff;font-size:18px;font-weight:bold;">
                            {{ $brandName }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;">
                            <h1 style="margin:0 0 12px;font-size:20px;color:#1f2937;">{{ $heading }}</h1>
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.5;">{{ $greeting }},</p>
                            @foreach($lines as $line)
                                <p style="margin:0 0 12px;font-size:15px;line-height:1.5;">{{ $line }}</p>
                            @endforeach
                            @if($actionUrl)
                                <p style="margin:22px 0 6px;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#800000;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:9px;">{{ $actionLabel }}</a>
                                </p>
                            @endif
                            <p style="margin:22px 0 0;font-size:13px;color:#6b7280;">If you did not expect this message you can ignore it. Please do not reply directly to this email.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 26px;background:#faf7f4;color:#8a8a8a;font-size:12px;">
                            &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @if(! empty($trackingUrl))
        <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;outline:none;" aria-hidden="true">
    @endif
</body>
</html>
