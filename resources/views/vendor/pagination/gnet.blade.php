@if ($paginator->hasPages())
    <div class="mt-4 border-t border-[#D7E4F3] pt-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-[#5F6B7A]">
                Mostrando
                <span class="font-black text-[#1A2B42]">{{ $paginator->firstItem() }}</span>
                a
                <span class="font-black text-[#1A2B42]">{{ $paginator->lastItem() }}</span>
                de
                <span class="font-black text-[#1A2B42]">{{ number_format($paginator->total()) }}</span>
                registros
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Anterior --}}
                @if ($paginator->onFirstPage())
                    <span
                        class="inline-flex h-9 min-w-22 cursor-not-allowed items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#9AA6B2] opacity-70"
                    >
                        Anterior
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        rel="prev"
                        class="inline-flex h-9 min-w-22 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm transition hover:bg-[#F7F9FC]"
                    >
                        Anterior
                    </button>
                @endif

                {{-- Números --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-transparent px-3 text-sm font-black text-[#5F6B7A]"
                        >
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-[#2E8BC0] bg-[#2E8BC0] px-3 text-sm font-black text-white shadow-sm"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-3 text-sm font-bold text-[#1A2B42] shadow-sm transition hover:bg-[#F7F9FC]"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Siguiente --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        rel="next"
                        class="inline-flex h-9 min-w-22 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm transition hover:bg-[#F7F9FC]"
                    >
                        Siguiente
                    </button>
                @else
                    <span
                        class="inline-flex h-9 min-w-22 cursor-not-allowed items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#9AA6B2] opacity-70"
                    >
                        Siguiente
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif