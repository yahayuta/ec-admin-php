<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | EC Admin</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; color: #1f2933; background: #f5f7fa; }
        header { background: #243b53; color: #fff; padding: 12px 24px; display: flex; gap: 24px; align-items: center; }
        header a { color: #fff; text-decoration: none; }
        header strong { margin-right: 16px; }
        main { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        h1 { font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #d9e2ec; text-align: left; }
        td.num, th.num { text-align: right; }
        .notice { padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
        .notice.ok { background: #e3f9e5; }
        .notice.error { background: #ffe3e3; }
        .filters, .actions { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .badge { padding: 2px 8px; border-radius: 10px; background: #d9e2ec; font-size: .85rem; }
        .badge.low { background: #ffe3e3; }
        form.inline { display: inline; }
    </style>
</head>
<body>
<header>
    <strong>EC Admin</strong>
    <a href="{{ route('orders.index') }}">受注</a>
    <a href="{{ route('products.index') }}">在庫</a>
</header>
<main>
    @if (session('status'))
        <div class="notice ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice error">
            @foreach ($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
