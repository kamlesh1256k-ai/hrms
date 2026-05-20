<?php

namespace App\Services;

class TaxRegimeComparisonService
{
    public function compare(float $annualIncome, float $oldRegimeDeductions): array
    {
        $oldTaxable = max($annualIncome - max($oldRegimeDeductions, 0), 0);
        $newTaxable = max($annualIncome - 75000, 0); // std deduction assumption

        $oldTax = $this->calculateOldRegimeTax($oldTaxable);
        $newTax = $this->calculateNewRegimeTax($newTaxable);

        return [
            'annual_income' => round($annualIncome, 2),
            'old_regime' => [
                'taxable_income' => round($oldTaxable, 2),
                'estimated_tax' => round($oldTax, 2),
            ],
            'new_regime' => [
                'taxable_income' => round($newTaxable, 2),
                'estimated_tax' => round($newTax, 2),
            ],
            'recommended' => $oldTax <= $newTax ? 'old' : 'new',
        ];
    }

    protected function calculateOldRegimeTax(float $income): float
    {
        if ($income <= 250000) {
            return 0;
        }
        if ($income <= 500000) {
            return ($income - 250000) * 0.05;
        }
        if ($income <= 1000000) {
            return 12500 + (($income - 500000) * 0.20);
        }
        return 112500 + (($income - 1000000) * 0.30);
    }

    protected function calculateNewRegimeTax(float $income): float
    {
        $slabs = [
            [400000, 0.00],
            [800000, 0.05],
            [1200000, 0.10],
            [1600000, 0.15],
            [2000000, 0.20],
            [2400000, 0.25],
        ];

        $tax = 0.0;
        $lower = 0.0;
        foreach ($slabs as [$upper, $rate]) {
            if ($income > $upper) {
                $tax += ($upper - $lower) * $rate;
                $lower = $upper;
            } else {
                $tax += ($income - $lower) * $rate;
                return max($tax, 0);
            }
        }

        if ($income > 2400000) {
            $tax += ($income - 2400000) * 0.30;
        }

        return max($tax, 0);
    }
}

