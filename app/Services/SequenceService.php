<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    public function generateCifNumber(string $branchCode): string
    {
        $year = date('y');
        $prefix = "{$branchCode}{$year}";

        return $this->generate('cif', $prefix, 7);
    }

    public function generateAccountNumber(string $productCode, string $branchCode): string
    {
        $prefix = "{$productCode}{$branchCode}";

        return $this->generate('account', $prefix, 9);
    }

    public function generateJournalNumber(): string
    {
        $prefix = 'JRN'.date('Ymd');

        return $this->generate('journal', $prefix, 5);
    }

    private function generate(string $type, string $prefix, int $padding): string
    {
        return DB::transaction(function () use ($type, $prefix, $padding): string {
            $sequence = Sequence::query()
                ->where('type', $type)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = Sequence::create([
                    'type' => $type,
                    'prefix' => $prefix,
                    'last_number' => 0,
                    'padding' => $padding,
                ]);
            }

            $sequence->increment('last_number');
            $sequence->refresh();

            return $prefix.str_pad((string) $sequence->last_number, $padding, '0', STR_PAD_LEFT);
        });
    }
}
