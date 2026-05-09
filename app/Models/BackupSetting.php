<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupSetting extends Model
{
    public const DAY_OPTIONS = [
        'mon' => 'Senin',
        'tue' => 'Selasa',
        'wed' => 'Rabu',
        'thu' => 'Kamis',
        'fri' => 'Jumat',
        'sat' => 'Sabtu',
        'sun' => 'Minggu',
    ];

    protected $fillable = [
        'enabled',
        'timezone',
        'run_time',
        'run_days',
        'start_date',
        'end_date',
        'keep_days',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'run_days' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'keep_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'enabled' => false,
                'timezone' => 'Asia/Jakarta',
                'run_time' => '01:00',
                'run_days' => array_keys(self::DAY_OPTIONS),
                'keep_days' => 7,
            ]
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function normalizedRunDays(): array
    {
        $days = collect($this->run_days ?: array_keys(self::DAY_OPTIONS))
            ->map(fn ($day) => strtolower((string) $day))
            ->filter(fn ($day) => array_key_exists($day, self::DAY_OPTIONS))
            ->unique()
            ->values()
            ->all();

        return $days === [] ? array_keys(self::DAY_OPTIONS) : $days;
    }

    public function isDueAt(CarbonImmutable $dateTime): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $local = $dateTime->setTimezone($this->timezone ?: 'Asia/Jakarta');

        if ($this->start_date && $local->startOfDay()->lt(CarbonImmutable::parse($this->start_date, $local->timezone)->startOfDay())) {
            return false;
        }

        if ($this->end_date && $local->startOfDay()->gt(CarbonImmutable::parse($this->end_date, $local->timezone)->startOfDay())) {
            return false;
        }

        if (! in_array(strtolower($local->format('D')), $this->normalizedRunDays(), true)) {
            return false;
        }

        return $local->format('H:i') === $this->run_time;
    }

    public function nextRunAfter(CarbonImmutable $dateTime): ?CarbonImmutable
    {
        if (! $this->enabled) {
            return null;
        }

        $local = $dateTime->setTimezone($this->timezone ?: 'Asia/Jakarta')->startOfMinute();
        [$hour, $minute] = array_map('intval', explode(':', $this->run_time));

        for ($offset = 0; $offset <= 370; $offset++) {
            $candidate = $local->addDays($offset)->setTime($hour, $minute);

            if ($candidate->lte($local)) {
                continue;
            }

            if ($this->start_date && $candidate->startOfDay()->lt(CarbonImmutable::parse($this->start_date, $candidate->timezone)->startOfDay())) {
                continue;
            }

            if ($this->end_date && $candidate->startOfDay()->gt(CarbonImmutable::parse($this->end_date, $candidate->timezone)->startOfDay())) {
                return null;
            }

            if (in_array(strtolower($candidate->format('D')), $this->normalizedRunDays(), true)) {
                return $candidate;
            }
        }

        return null;
    }
}
