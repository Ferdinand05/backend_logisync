<x-filament-widgets::widget>
    <x-filament::section heading="Stock opname" description="Progres sesi opname aktif dan lokasi yang masih berjalan.">
        @if ($sessions->isEmpty())
            <div class="py-8 text-center">
                <p class="text-sm font-medium text-gray-950 dark:text-white">Tidak ada opname aktif</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sesi dengan status in progress atau reviewing akan tampil di sini.</p>
            </div>
        @else
            <div class="mb-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $totalItems > 0 ? number_format($scannedItems / $totalItems * 100, 1) : '0.0' }}%</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($scannedItems) }} dari {{ number_format($totalItems) }} barang telah discan</span>
            </div>
            <div class="space-y-4">
                @foreach ($sessions as $session)
                    @php
                        $total = $session->auditItems->count();
                        $scanned = $session->auditItems->whereNotNull('actual_qty')->count();
                        $progress = $total > 0 ? round($scanned / $total * 100) : 0;
                        $missing = $session->auditItems->whereNull('actual_qty')->count();
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-950 dark:text-white">{{ $session->audit_number }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->location?->name ?? 'Lokasi tidak tersedia' }} · {{ $scanned }}/{{ $total }} barang</p>
                            </div>
                            <span class="text-sm font-medium tabular-nums text-gray-700 dark:text-gray-300">{{ $progress }}%</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres {{ $session->audit_number }}">
                            <div class="h-full rounded-full bg-primary-600 transition-[width] duration-200" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($missing) }} barang belum ditemukan atau belum discan</p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
