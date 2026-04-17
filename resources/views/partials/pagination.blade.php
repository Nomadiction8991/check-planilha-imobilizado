@if ($paginator->hasPages())
    <div class="pagination">
        <div class="table-note">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}.
            {{ $paginator->total() }} registro(s).
        </div>
        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="disabled">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}">Anterior</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}">Próxima</a>
            @else
                <span class="disabled">Próxima</span>
            @endif
        </div>
    </div>
@endif
