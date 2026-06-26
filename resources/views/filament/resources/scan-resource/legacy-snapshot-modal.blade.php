@php
    $payloadSize = strlen((string) $snapshot->payload);
    $metadata = $snapshot->metadata ?? [];
    $payloadSizeLabel = $payloadSize < 256
        ? 'Minimal / empty payload ('.number_format($payloadSize).' bytes)'
        : number_format($payloadSize).' bytes';
    $savedReportLength = (int) ($metadata['saved_report_length'] ?? $payloadSize);
    $savedReportLengthLabel = $savedReportLength < 256
        ? 'Minimal / empty payload ('.number_format($savedReportLength).' bytes)'
        : number_format($savedReportLength).' bytes';
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        This is an archived legacy report snapshot. The original SavedReport payload is stored safely, but old HTML is not rendered in admin.
    </div>

    <dl class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Legacy Table</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-950">{{ $snapshot->legacy_table }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Legacy ID</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-950">{{ $snapshot->legacy_id }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Source URL</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-950">{{ $snapshot->source_url ?: 'N/A' }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payload Size</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950">{{ $payloadSizeLabel }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Saved Report Length</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950">{{ $savedReportLengthLabel }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payload Hash</dt>
            <dd class="mt-1 break-all font-mono text-xs text-gray-950">{{ $snapshot->payload_hash }}</dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Legacy Created</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950">
                {{ $snapshot->legacy_created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?? 'N/A' }}{{ $snapshot->legacy_created_at ? ' IST' : '' }}
            </dd>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Archived In QSA</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950">
                {{ $snapshot->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?? 'N/A' }}{{ $snapshot->created_at ? ' IST' : '' }}
            </dd>
        </div>
    </dl>
</div>
