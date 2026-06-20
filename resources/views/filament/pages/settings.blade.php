<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::section>
            <x-slot name="heading">Application</x-slot>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-950">{{ config('app.name') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Public URL</dt>
                    <dd class="mt-1 text-sm text-gray-950">{{ config('app.url') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Scan timeout</dt>
                    <dd class="mt-1 text-sm text-gray-950">{{ config('qsa.scan_timeout') }} seconds</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Queue connection</dt>
                    <dd class="mt-1 text-sm text-gray-950">{{ config('queue.default') }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
