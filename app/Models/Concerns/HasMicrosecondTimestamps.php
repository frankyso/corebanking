<?php

namespace App\Models\Concerns;

trait HasMicrosecondTimestamps
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * Override setAttribute to ensure date-only columns (cast as 'date')
     * are stored as 'Y-m-d' instead of the microsecond format.
     *
     * Without this, SQLite stores date columns as 'Y-m-d H:i:s.u' which
     * breaks string comparisons like WHERE date <= '2026-03-11'.
     */
    public function setAttribute($key, $value)
    {
        if ($value !== null && $this->hasCast($key, ['date', 'immutable_date'])) {
            $this->attributes[$key] = $this->asDateTime($value)->format('Y-m-d');

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
