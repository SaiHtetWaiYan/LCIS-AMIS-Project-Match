@props(['paginator'])

@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pages = [];

    if ($lastPage <= 7) {
        $pages = range(1, $lastPage);
    } else {
        $pages[] = 1;
        if ($currentPage > 3) {
            $pages[] = '...';
        }
        for ($page = max(2, $currentPage - 1); $page <= min($lastPage - 1, $currentPage + 1); $page++) {
            $pages[] = $page;
        }
        if ($currentPage < $lastPage - 2) {
            $pages[] = '...';
        }
        $pages[] = $lastPage;
    }

    $box = 'inline-flex items-center justify-center w-9 h-9 rounded-md text-sm';
    $link = $box.' border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
    $disabled = $box.' border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-400 cursor-not-allowed';
@endphp

<div class="flex flex-wrap items-center justify-between gap-4 pt-4">
    <p class="text-sm text-gray-700 dark:text-gray-300">
        {{ __('app.pagination.showing', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => number_format($paginator->total())]) }}
    </p>

    @if ($lastPage > 1)
        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            @if ($currentPage > 1)
                <a href="{{ $paginator->previousPageUrl() }}" class="{{ $link }}" aria-label="{{ __('app.pagination.previous') }}">&lsaquo;</a>
            @else
                <span class="{{ $disabled }}" aria-hidden="true">&lsaquo;</span>
            @endif

            @foreach ($pages as $page)
                @if ($page === '...')
                    <span class="{{ $box }} text-gray-500">&hellip;</span>
                @elseif ($page === $currentPage)
                    <span class="{{ $box }} bg-green-500 font-semibold text-white" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="{{ $link }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="{{ $link }}" aria-label="{{ __('app.pagination.next') }}">&rsaquo;</a>
            @else
                <span class="{{ $disabled }}" aria-hidden="true">&rsaquo;</span>
            @endif
        </nav>
    @endif
</div>
