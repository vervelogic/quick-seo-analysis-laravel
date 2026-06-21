<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top scanned domains</x-slot>

        <div class="space-y-3">
            @forelse ($this->domains() as $domain => $count)
                <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3">
                    <span class="truncate text-sm font-medium text-gray-950">{{ $domain }}</span>
                    <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">{{ $count }} scans</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">No scans yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
