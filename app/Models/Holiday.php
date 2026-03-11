<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'date',
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function isHoliday(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return static::query()
            ->whereDate('date', $date->toDateString())
            ->exists();
    }

    public static function getNextBusinessDay(Carbon $date): Carbon
    {
        $next = $date->copy()->addDay();

        while (static::isHoliday($next)) {
            $next->addDay();
        }

        return $next;
    }
}
