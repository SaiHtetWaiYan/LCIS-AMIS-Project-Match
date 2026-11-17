<?php

namespace App\Models;

use App\Enums\UserSide;
use Database\Factories\ApiClientFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An external system (e.g. AMIS or LCIS) allowed to read /api/v1 with a bearer token.
 * Created and revoked with the api-client:create and api-client:revoke commands. Requests are
 * authenticated by token_hash; the encrypted token is kept so users of the client's side can
 * read it in the guide.
 */
class ApiClient extends Model
{
    /** @use HasFactory<ApiClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'side', 'token_hash', 'token', 'is_active', 'last_used_at'];

    /**
     * @var list<string>
     */
    protected $hidden = ['token_hash', 'token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => UserSide::class,
            'token' => 'encrypted',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function generateToken(): string
    {
        return Str::random(80);
    }

    public static function findActiveByToken(string $plainToken): ?self
    {
        return static::query()->active()->where('token_hash', static::hashToken($plainToken))->first();
    }

    /**
     * The newest active client per side whose token can be shown, keyed by side value.
     *
     * @param  list<UserSide>  $sides
     * @return array<string, self>
     */
    public static function shownInGuideFor(array $sides): array
    {
        return static::query()
            ->active()
            ->whereNotNull('token')
            ->whereIn('side', $sides)
            ->latest('id')
            ->get()
            ->unique(fn (self $client): string => $client->side->value)
            ->keyBy(fn (self $client): string => $client->side->value)
            ->all();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
