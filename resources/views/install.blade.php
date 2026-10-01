<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Installatie – Orivé</title>
  <style>
    body { margin: 0; padding: 40px 16px; background: #f2efe4; color: #2b2b2b; font: 15px/1.6 Raleway, system-ui, sans-serif; }
    .box { max-width: 620px; margin: 0 auto; padding: 32px; border-radius: 24px; background: #fff; }
    h1 { margin: 0 0 6px; font: 400 30px/1.2 Georgia, serif; color: #52572e; }
    ul { padding: 0; list-style: none; }
    li { padding: 4px 0; }
    .ok::before { content: "✓ "; color: #3d7a2a; font-weight: 700; }
    .bad { color: #b42318; } .bad::before { content: "✗ "; font-weight: 700; }
    label { display: block; margin: 14px 0 4px; font-weight: 600; }
    input[type=text], input[type=email], input[type=password] { width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1px solid #d6d3c8; border-radius: 12px; font: inherit; }
    .check { display: flex; gap: 8px; align-items: flex-start; font-weight: 400; }
    button { margin-top: 22px; padding: 13px 26px; border: 0; border-radius: 999px; background: #52572e; color: #fff; font: 600 15px Raleway, system-ui, sans-serif; cursor: pointer; }
    .muted { color: #6b6c6b; font-size: 13px; }
    .error { padding: 10px 14px; border-radius: 12px; background: #fdecea; color: #b42318; }
    a { color: #52572e; }
  </style>
</head>
<body>
  <div class="box">
    @if($done)
      <h1>Klaar!</h1>
      <p>De webshop is geïnstalleerd.</p>
      <ul>@foreach($log as $line)<li class="ok">{{ $line }}</li>@endforeach</ul>
      <p><strong>Belangrijk:</strong> maak de regel <code>INSTALL_TOKEN=</code> in je .env-bestand leeg. Deze pagina is nu al uitgeschakeld omdat er een beheerder bestaat.</p>
      <p><a href="{{ url('/beheer') }}">Naar het beheer →</a> &nbsp; <a href="{{ url('/') }}">Bekijk de winkel →</a></p>
    @else
      <h1>Webshop installeren</h1>
      <p class="muted">Eenmalige installatie. Zie INSTALLATIE.md voor de stappen.</p>
      <ul>
        @foreach($checks as $label => $ok)<li class="{{ $ok ? 'ok' : 'bad' }}">{{ $label }}</li>@endforeach
      </ul>
      @if(! empty($error))<p class="error">{{ $error }}</p>@endif
      <form method="post" action="{{ url('/install') }}">
        <label for="token">Installatiecode <span class="muted">(INSTALL_TOKEN uit .env)</span></label>
        <input id="token" type="text" name="token" required autocomplete="off">
        <label for="name">Jouw naam</label>
        <input id="name" type="text" name="name" value="{{ request('name') }}" required>
        <label for="email">E-mailadres (om in te loggen op /beheer)</label>
        <input id="email" type="email" name="email" value="{{ request('email') }}" required>
        <label for="password">Wachtwoord <span class="muted">(minimaal 10 tekens)</span></label>
        <input id="password" type="password" name="password" required minlength="10">
        <label class="check"><input type="checkbox" name="import" value="1" checked> Producten, collecties, blog, pagina's en foto's overzetten uit orivenature.com (kan een paar minuten duren)</label>
        <button type="submit">Installeren</button>
      </form>
    @endif
  </div>
</body>
</html>
