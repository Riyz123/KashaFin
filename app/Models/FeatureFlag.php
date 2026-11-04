<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Gates access to a whole section of the student app (see the migration's
 * docblock) without ever touching the data inside it. isEnabled()/label()
 * are the only two things the rest of the app should call — everything
 * reads from one process-lifetime cache entry so a disabled chatbot tool
 * or a greyed-out sidebar link doesn't cost a query on every request.
 */
class FeatureFlag extends Model
{
    use LogsActivity;

    private const CACHE_KEY = 'feature_flags:all';

    // Seeding fresh flags shouldn't clutter the activity log — only an
    // actual admin toggle (an "updated" event) is worth tracing.
    protected static $doNotRecordEvents = ['created', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('feature_flag')
            ->setDescriptionForEvent(fn (string $event) => ($this->is_enabled ? 'Activó' : 'Desactivó')." la sección \"{$this->label}\"");
    }

    protected $fillable = [
        'key', 'label', 'description', 'is_enabled', 'disabled_at', 'disabled_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'disabled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    public static function isEnabled(string $key): bool
    {
        return (bool) (self::cached()[$key]['is_enabled'] ?? true);
    }

    public static function label(string $key): string
    {
        return self::cached()[$key]['label'] ?? 'Esta sección';
    }

    public static function disabledMessage(string $key): string
    {
        return 'La sección "'.self::label($key).'" está deshabilitada por el momento.';
    }

    /**
     * Convenience for the chatbot intents: null means "keep going", a
     * string means "stop here and show this instead" — avoids repeating
     * `if (! isEnabled) return disabledMessage(...)` at every call site.
     */
    public static function requireEnabled(string $key): ?string
    {
        return self::isEnabled($key) ? null : self::disabledMessage($key);
    }

    /**
     * @return array<string, array{is_enabled: bool, label: string}>
     */
    private static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->get()
            ->mapWithKeys(fn (self $flag) => [$flag->key => ['is_enabled' => $flag->is_enabled, 'label' => $flag->label]])
            ->all());
    }
}
