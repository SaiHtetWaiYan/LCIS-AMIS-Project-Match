<x-badge :tone="match ($run->status) { App\Enums\SyncStatus::Succeeded => 'green', App\Enums\SyncStatus::Failed => 'red', App\Enums\SyncStatus::Running => 'blue' }">
    {{ $run->status->label() }}
</x-badge>
