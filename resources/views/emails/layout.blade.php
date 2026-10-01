@php($olive = '#52572e')
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? settings('store.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3efe3;font-family:Raleway,'Helvetica Neue',Arial,sans-serif;color:#2d2e2d;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3efe3;">
    <tr><td align="center" style="padding:28px 12px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;">
        <tr><td align="center" style="padding:6px 0 22px;font-family:Georgia,'Libre Baskerville',serif;font-size:26px;letter-spacing:4px;color:#2d2e2d;">
          {{ strtoupper(settings('store.name')) }}
        </td></tr>
        <tr><td style="background:#ffffff;border-radius:22px;padding:34px 30px;">
          @yield('content')
        </td></tr>
        <tr><td align="center" style="padding:22px 10px;font-size:12px;line-height:1.6;color:#6b6c6b;">
          {{ settings('store.legal_name') }} · {!! nl2br(e(str_replace("\n", ' · ', (string) settings('store.address')))) !!}<br>
          {{ settings('store.email') }} · KvK {{ settings('store.kvk') }}
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
