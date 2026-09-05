{{--
    Plain pagination partial for the admin area. Laravel's built-in
    pagination views assume Tailwind (or Bootstrap) is loaded — the admin
    pages load neither, so those views render as unstyled/broken utility
    class soup. This mirrors the "Zurück / Seite X von Y / Weiter" pattern
    already used by the public Vue pagination bar.
--}}
@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="admin-pagination-link is-disabled">&laquo; Zurück</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="admin-pagination-link">&laquo; Zurück</a>
        @endif

        <span class="admin-pagination-status">
            Seite {{ $paginator->currentPage() }} von {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="admin-pagination-link">Weiter &raquo;</a>
        @else
            <span class="admin-pagination-link is-disabled">Weiter &raquo;</span>
        @endif
    </nav>
@endif
