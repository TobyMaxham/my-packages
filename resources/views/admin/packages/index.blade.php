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
@endpush

@section('content')
    <form method="GET" action="{{ route('admin.packages.index') }}" style="margin-bottom: 1.5rem;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Paket suchen …">
        <button type="submit">Suchen</button>
    </form>

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
            @method('PATCH')
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="page" value="{{ $packages->currentPage() }}">

            <div>{{ $package->name }}</div>
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
        {{ $packages->links() }}
    </div>
@endsection
