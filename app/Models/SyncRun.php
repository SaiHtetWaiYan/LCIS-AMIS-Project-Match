<?php

namespace App\Models;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use Database\Factories\SyncRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

/**
 * One sync of a source: when it ran, what changed and, if it failed, why.
 */
#[Fillable(['source', 'status', 'triggered_by', 'started_at', 'finished_at', 'expected_count', 'fetched_count', 'created_count', 'updated_count', 'unchanged_count', 'missing_count', 'restored_count', 'error_code', 'error_message', 'details'])]
class SyncRun extends Model
{
    /** @use HasFactory<SyncRunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => SyncSource::class,
            'status' => SyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'details' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forSource(Builder $query, SyncSource $source): void
    {
        $query->where('source', $source);
    }

    /**
     * A readable name for the error code, falling back to a generic label for codes we do not know.
     */
    public function errorLabel(): string
    {
        $key = "sync.error_codes.{$this->error_code}";

        return Lang::has($key) ? __($key) : __('sync.fields.error');
    }

    public function isAuthenticationFailure(): bool
    {
        return in_array($this->error_code, ['API_KEY_INVALID', 'API_KEY_INACTIVE', 'API_KEY_EXPIRED', 'UNAUTHENTICATED', 'HTTP_401', 'HTTP_403'], true);
    }
}
