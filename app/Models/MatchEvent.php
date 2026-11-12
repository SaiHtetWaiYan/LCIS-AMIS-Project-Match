<?php

namespace App\Models;

use App\Enums\MatchAction;
use App\Enums\MatchStatus;
use App\Enums\UserSide;
use Database\Factories\MatchEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One entry of a match's audit log. Append-only: existing events cannot be changed or deleted.
 */
class MatchEvent extends Model
{
    /** @use HasFactory<MatchEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Match events are append-only.'));
        static::deleting(fn (): never => throw new LogicException('Match events are append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => UserSide::class,
            'action' => MatchAction::class,
            'from_status' => MatchStatus::class,
            'to_status' => MatchStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProjectMatch, $this>
     */
    public function projectMatch(): BelongsTo
    {
        return $this->belongsTo(ProjectMatch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
