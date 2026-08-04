@php($record = $getRecord())
<div class="min-w-40 space-y-1">
    <div class="text-sm font-medium">{{ $record->current_quantity }} / {{ $record->target_quantity }} — {{ number_format($record->progress_percentage, 2, ',', '.') }}%</div>
    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-full rounded-full bg-primary-600" style="width: {{ $record->progress_percentage }}%"></div>
    </div>
</div>
