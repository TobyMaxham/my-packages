<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — @yield('title')</title>
    <style>
        @import url('https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700');

        :root {
            --a-bg: #f7f8fa;
            --a-surface: #ffffff;
            --a-ink: #1b1f27;
            --a-ink-muted: #5b6270;
            --a-border: #e3e6ec;
            --a-accent: #3457d5;
            --a-accent-hover: #2843ad;
            --a-radius: 10px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
            background: var(--a-bg);
            color: var(--a-ink);
        }
        h1 { font-size: 1.6rem; margin: 0 0 0.25rem; }
        a { color: var(--a-accent); }
        .top-bar { display: flex; justify-content: space-between; align-items: flex-start; }
        .back-link { display: inline-block; font-size: 0.85rem; text-decoration: none; margin-bottom: 0.75rem; }
        .session-info { color: var(--a-ink-muted); font-size: 0.9rem; margin: 0 0 1.5rem; }
        button[type="submit"].logout-btn {
            background: transparent; border: 1px solid var(--a-border); color: var(--a-ink);
            border-radius: 6px; padding: 0.5rem 1rem; font: inherit; cursor: pointer;
        }
        button[type="submit"].logout-btn:hover { border-color: var(--a-accent); color: var(--a-accent); }
        nav.admin-nav {
            display: flex; gap: 1.5rem; border-bottom: 1px solid var(--a-border);
            margin-bottom: 1.75rem;
        }
        nav.admin-nav a {
            color: var(--a-ink-muted); text-decoration: none; font-weight: 500;
            padding-bottom: 0.85rem; border-bottom: 2px solid transparent;
        }
        nav.admin-nav a.active { color: var(--a-ink); border-bottom-color: var(--a-accent); }
        .status {
            background: #e7f5ee; color: #1e8e5a; padding: 0.6rem 0.9rem;
            border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;
        }
        .errors {
            background: #fbe9e5; color: #c4432b; padding: 0.6rem 0.9rem;
            border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;
        }
        .errors ul { margin: 0; padding-left: 1.2rem; }
        table {
            width: 100%; border-collapse: collapse; background: var(--a-surface);
            border: 1px solid var(--a-border); border-radius: var(--a-radius); overflow: hidden;
        }
        th, td { text-align: left; padding: 0.75rem 1rem; border-bottom: 1px solid var(--a-border); vertical-align: top; font-size: 0.9rem; }
        th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; color: var(--a-ink-muted); font-weight: 600; background: #fafbfc; }
        tbody tr:last-child td, tr:last-child { border-bottom: none; }
        button[type="submit"] {
            background: var(--a-accent); color: #fff; border: none; border-radius: 6px;
            padding: 0.5rem 1rem; font: inherit; font-weight: 600; cursor: pointer;
        }
        button[type="submit"]:hover { background: var(--a-accent-hover); }
        input[type="text"], select, textarea {
            border: 1px solid var(--a-border); border-radius: 6px; padding: 0.5rem 0.65rem;
            font: inherit; background: var(--a-surface); color: var(--a-ink);
        }
        input[type="text"]:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--a-accent); box-shadow: 0 0 0 3px rgba(52, 87, 213, 0.15);
        }
        .admin-pagination { display: flex; align-items: center; gap: 1.25rem; margin-top: 1.5rem; }
        .admin-pagination-link { color: var(--a-accent); text-decoration: none; font-size: 0.9rem; }
        .admin-pagination-link.is-disabled { color: var(--a-ink-muted); pointer-events: none; }
        .admin-pagination-status { color: var(--a-ink-muted); font-size: 0.9rem; }
        @stack('styles')
    </style>
</head>
<body>
    <a class="back-link" href="{{ route('packages.dashboard.home') }}">&larr; Zur Paketliste</a>

    <div class="top-bar">
        <h1>@yield('title')</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
    <p class="session-info">Eingeloggt als {{ auth()->user()->email }}</p>

    <nav class="admin-nav">
        <a href="{{ route('admin.vendors.index') }}" class="{{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">Vendors</a>
        <a href="{{ route('admin.packages.index') }}" class="{{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">Pakete</a>
    </nav>

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</body>
</html>
