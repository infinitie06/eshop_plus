@if ($paginator->hasPages())
    <nav class="w-100 d-flex justify-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ labels('panel_labels.previous', 'Previous') }}</span>
                    </li>
                @else
                    @php
                        $queryParams = [
                            'page' => $paginator->currentPage() - 1,
                        ];
                        $urlWithQueryParams =
                            url()->current() . '?' . http_build_query(array_merge(Request::query(), $queryParams));
                    @endphp
                    <li class="page-item">
                        <a wire:navigate class="page-link" href="{{ customUrl($urlWithQueryParams) }}"
                            rel="prev">{{ labels('panel_labels.previous', 'Previous') }}</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    @php
                        $queryParams = [
                            'page' => $paginator->currentPage() + 1,
                        ];
                        $urlWithQueryParams =
                            url()->current() . '?' . http_build_query(array_merge(Request::query(), $queryParams));
                    @endphp
                    <li class="page-item">
                        <a wire:navigate class="page-link" href="{{ customUrl($urlWithQueryParams) }}"
                            rel="next">{{ labels('panel_labels.next', 'Next') }}</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ labels('panel_labels.next', 'Next') }}</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                <p class="small text-muted">
                    {!! labels('panel_labels.showing', 'Showing') !!}
                    <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                    {!! labels('panel_labels.pagination_to', 'to') !!}
                    <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                    {!! labels('panel_labels.pagination_of', 'of') !!}
                    <span class="fw-semibold">{{ $paginator->total() }}</span>
                    {!! labels('panel_labels.pagination_results', 'results') !!}
                </p>
            </div>

            <div>
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="{{ labels('panel_labels.previous', 'Previous') }}">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        @php
                            $queryParams = [
                                'page' => $paginator->currentPage() - 1,
                            ];
                            $urlWithQueryParams =
                                url()->current() . '?' . http_build_query(array_merge(Request::query(), $queryParams));
                        @endphp
                        <li class="page-item">
                            <a wire:navigate class="page-link" href="{{ customUrl($urlWithQueryParams) }}"
                                rel="prev" aria-label="{{ labels('panel_labels.previous', 'Previous') }}">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span
                                    class="page-link">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @php
                                    $queryParams = [
                                        'page' => $page,
                                    ];
                                    $urlWithQueryParams =
                                        url()->current() .
                                        '?' .
                                        http_build_query(array_merge(Request::query(), $queryParams));
                                @endphp
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span
                                            class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a wire:navigate class="page-link"
                                            href="{{ customUrl($urlWithQueryParams) }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        @php
                            $queryParams = [
                                'page' => $paginator->currentPage() + 1,
                            ];
                            $urlWithQueryParams =
                                url()->current() . '?' . http_build_query(array_merge(Request::query(), $queryParams));
                        @endphp
                        <li class="page-item">
                            <a wire:navigate class="page-link" href="{{ customUrl($urlWithQueryParams) }}"
                                rel="next" aria-label="{{ labels('panel_labels.next', 'Next') }}">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="{{ labels('panel_labels.next', 'Next') }}">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
