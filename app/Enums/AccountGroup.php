<?php

namespace App\Enums;

enum AccountGroup: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Aset',
            self::Liability => 'Kewajiban',
            self::Equity => 'Ekuitas',
            self::Revenue => 'Pendapatan',
            self::Expense => 'Beban',
        };
    }

    public function codePrefix(): string
    {
        return match ($this) {
            self::Asset => '1',
            self::Liability => '2',
            self::Equity => '3',
            self::Revenue => '4',
            self::Expense => '5',
        };
    }
}
