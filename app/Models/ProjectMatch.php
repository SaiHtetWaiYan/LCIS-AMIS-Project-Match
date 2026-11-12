<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Enums\UserSide;
use Database\Factories\ProjectMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An LCIS project paired with an AMIS contract. Only App\Services\Matches\MatchWorkflow changes
 * it (attributes are guarded), so every change is checked and logged in match_events.
 */
class ProjectMatch extends Model
{
    /** @use HasFactory<ProjectMatchFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'lcis_confirmed_at' => 'datetime',
            'amis_confirmed_at' => 'datetime',
            'matched_at' => 'datetime',
            'rejected_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'unmatched_at' => 'datetime',
            'needs_review' => 'boolean',
            'review_reasons' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LcisProject, $this>
     */
    public function lcisProject(): BelongsTo
    {
        return $this->belongsTo(LcisProject::class);
    }

    /**
     * @return BelongsTo<AmisContract, $this>
     */
    public function amisContract(): BelongsTo
    {
        return $this->belongsTo(AmisContract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lcisConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lcis_confirmed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function amisConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amis_confirmed_by');
    }

    /**
     * @return HasMany<MatchEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', MatchStatus::active());
    }

    /**
     * Proposals still waiting for a confirmation from the given side.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingSide(Builder $query, UserSide $side): void
    {
        $query->where('status', MatchStatus::Proposed)
            ->whereNull($side === UserSide::Lcis ? 'lcis_confirmed_at' : 'amis_confirmed_at');
    }

    public function isConfirmedBy(UserSide $side): bool
    {
        return match ($side) {
            UserSide::Lcis => $this->lcis_confirmed_at !== null,
            UserSide::Amis => $this->amis_confirmed_at !== null,
            UserSide::Admin => false,
        };
    }
}
