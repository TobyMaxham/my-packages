@extends('admin.layout')

@section('title', 'Vendors')

@section('content')
    <form style="display: flex; gap: 0.5rem; margin-bottom: 2rem; align-items: end;" method="POST" action="{{ route('admin.vendors.store') }}">
        @csrf
        <div>
            <label for="name" style="display: block; font-size: 0.8rem; margin-bottom: 0.25rem;">Vendor-Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div>
            <label for="ecosystem" style="display: block; font-size: 0.8rem; margin-bottom: 0.25rem;">Ecosystem</label>
            <select id="ecosystem" name="ecosystem">
                <option value="composer" @selected(old('ecosystem', 'composer') === 'composer')>composer</option>
                <option value="npm" @selected(old('ecosystem') === 'npm')>npm</option>
            </select>
        </div>
        <button type="submit">Vendor hinzufügen &amp; scannen</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Ecosystem</th>
                <th>Pakete</th>
                <th>Angelegt</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendors as $vendor)
                <tr>
                    <td>{{ $vendor->name }}</td>
                    <td>{{ $vendor->ecosystem->value }}</td>
                    <td>{{ $vendor->packages_count }}</td>
                    <td>{{ $vendor->created_at->format('d.m.Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.vendors.rescan', $vendor) }}">
                            @csrf
                            <button type="submit">Jetzt neu scannen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Noch keine Vendors getrackt.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
