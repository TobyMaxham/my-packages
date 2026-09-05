@extends('admin.layout')

@section('title', 'Pakete')

@push('styles')
        .pkg-grid-header, .pkg-grid-row {
            display: grid;
            grid-template-columns: 2fr 1fr 5rem 3fr 6rem;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e3e6ec;
            align-items: center;
        }
        .pkg-grid-header { font-weight: 600; font-size: 0.85rem; color: #5b6270; }
        .pkg-grid-row textarea { width: 100%; box-sizing: border-box; font: inherit; }
        .pkg-name-cell { display: flex; align-items: center; gap: 0.5rem; }
        button[type="submit"].star-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0;
            color: #a1670f;
        }
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <form method="GET" action="{{ route('admin.packages.index') }}" style="display: flex; gap: 0.5rem;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Paket suchen …">
            <button type="submit">Suchen</button>
        </form>
        <a href="{{ route('api.admin.packages.export') }}" target="_blank" rel="noopener">Export als JSON &#8599;</a>
    </div>

    <div class="pkg-grid-header">
        <div>Paket</div>
        <div>Vendor</div>
        <div>Featured</div>
        <div>Featured-Note</div>
        <div></div>
    </div>

    @forelse ($packages as $package)
        <form method="POST" action="{{ route('admin.packages.update', $package) }}" class="pkg-grid-row">
            @csrf
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="page" value="{{ $packages->currentPage() }}">

            <div class="pkg-name-cell">
                <button
                    type="submit"
                    formaction="{{ route('admin.packages.toggle-featured', $package) }}"
                    class="star-btn"
                    title="{{ $package->is_featured ? 'Favorit entfernen' : 'Als Favorit markieren' }}"
                >{{ $package->is_featured ? '★' : '☆' }}</button>
                {{ $package->name }}
            </div>
            <div>{{ $package->vendor?->name ?? '—' }}</div>
            <div>
                <input type="checkbox" name="is_featured" value="1" @checked($package->is_featured)>
            </div>
            <div>
                <textarea name="featured_note" rows="2" maxlength="500" placeholder="Warum ist dieses Paket erwähnenswert?">{{ $package->featured_note }}</textarea>
            </div>
            <div>
                <button type="submit">Speichern</button>
            </div>
        </form>
    @empty
        <p>Keine Pakete gefunden.</p>
    @endforelse

    <div style="margin-top: 1.5rem;">
        {{ $packages->links('admin.pagination') }}
    </div>
@endsection
