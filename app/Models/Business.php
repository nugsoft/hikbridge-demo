<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A tenant. Each business owns its own HikBridge API key, which is scoped to
 * that business server-side — so users of one business can never authenticate
 * against another business's devices.
 *
 * @property int $id
 * @property string $name
 * @property string $hik_api_key
 * @property string|null $hik_base_url
 * @property string|null $hik_webhook_secret
 */
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'hik_api_key',
        'hik_base_url',
        'hik_webhook_secret',
    ];

    protected $hidden = [
        'hik_api_key',
        'hik_webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'hik_api_key' => 'encrypted',
            'hik_webhook_secret' => 'encrypted',
        ];
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * A masked view of the API key for display (never expose the full key).
     */
    public function maskedApiKey(): string
    {
        $key = (string) $this->hik_api_key;

        if (strlen($key) <= 12) {
            return str_repeat('•', max(strlen($key), 8));
        }

        return Str::substr($key, 0, 8).str_repeat('•', 6).Str::substr($key, -4);
    }
}
