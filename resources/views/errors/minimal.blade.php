{{--
    Self-contained error page (no database, no layout data) so it renders
    even when the application itself is failing.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') – {{ config('site.name') }}</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#fffcf7;color:#3d332e;font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;padding:24px;text-align:center}
        .code{font:600 72px/1 Georgia,serif;color:#e5761a;margin:0}
        h1{font:600 28px/1.25 Georgia,serif;color:#1f1714;margin:16px 0 8px}
        p{margin:0 auto;max-width:520px;color:#6b5f58}
        .actions{margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        a{display:inline-flex;align-items:center;min-height:48px;padding:0 24px;border-radius:999px;font-weight:600;text-decoration:none}
        .primary{background:#bd261f;color:#fff}.ghost{border:1px solid #1f171426;color:#1f1714}
    </style>
</head>
<body>
    <main>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="primary" href="/">Go to homepage</a>
            <a class="ghost" href="tel:{{ preg_replace('/[^0-9+]/', '', config('site.phone')) }}">Call {{ config('site.phone') }}</a>
        </div>
    </main>
</body>
</html>
