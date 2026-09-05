<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — @yield('title')</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 2rem auto; padding: 0 1rem; color: #1b1f27; }
        h1 { margin-bottom: 0.25rem; }
        .status { background: #e7f5ee; color: #1e8e5a; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        .errors { background: #fbe9e5; color: #c4432b; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e3e6ec; vertical-align: top; }
        button { cursor: pointer; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        nav.admin-nav { margin: 1rem 0 1.5rem; display: flex; gap: 1rem; border-bottom: 1px solid #e3e6ec; padding-bottom: 0.75rem; }
        nav.admin-nav a { color: #5b6270; text-decoration: none; }
        nav.admin-nav a.active { color: #1b1f27; font-weight: 600; }
        @stack('styles')
    </style>
</head>
<body>
    <div class="top-bar">
        <h1>@yield('title')</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
    <p>Eingeloggt als {{ auth()->user()->email }}</p>

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
