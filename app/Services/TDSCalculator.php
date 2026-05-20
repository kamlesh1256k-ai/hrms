<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\TaxDeclaration;
use App\Services\SalaryCalculator;

/**
 * TDS Calculator — New Tax Regime FY 2025-26 (Section 115BAC)
 *
 * Slabs (after ₹75,000 standard deduction):
 *   0 – 4,00,000       →  Nil
 *   4,00,001 – 8,00,000  →  5%
 *   8,00,001 – 12,00,000 → 10%
 *  12,00,001 – 16,00,000 → 15%
 *  16,00,001 – 20,00,000 → 20%
 *  20,00,001 – 24,00,000 → 25%
 *  Above 24,00,000       → 30%
 *
 * Rebate u/s 87A: If taxable income ≤ ₹7,00,000 → No tax
 * Health & Education Cess: 4% on tax
 *
 * Monthly TDS = Annual Tax / 12
 *
 * If employee has a TaxDeclaration with old regime AND it yields lower tax,
 * the old regime tax is used instead.
 */
class TDSCalculator
{
    const STANDARD_DEDUCTION = 75000;
    const REBATE_LIMIT = 700000;
    const CESS_RATE = 0.04;

    /**
     * Calculate monthly TDS for an employee.
     *
     * @param int    $employeeId
     * @param string $financialYear  e.g. '2025-2026'
     * @param float|null $annualGrossOverride  Override gross (for payroll run). If null, uses CTC.
     * @return array ['annual_tax' => float, 'monthly_tds' => float, 'regime' => 'new'|'old', 'taxable_income' => float, 'breakdown' => [...]]
     */
    public function calculate(int $employeeId, string $financialYear = '2025-2026', ?float $annualGrossOverride = null): array
    {
        // TDS is on GROSS EARNINGS (CTC minus employer contributions)
        $empSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        if ($annualGrossOverride) {
            $annualGross = $annualGrossOverride;
        } elseif ($empSalary) {
            $annualGross = $this->ctcToGross((float) $empSalary->ctc, (float) ($empSalary->basic_percentage ?: 50));
        } else {
            $annualGross = 0;
        }

        if ($annualGross <= 0) {
            return $this->zeroResult('new');
        }

        // Check if employee has a tax declaration for this FY
        $declaration = TaxDeclaration::where('employee_id', $employeeId)
            ->where('financial_year', $financialYear)
            ->first();

        $regime = $declaration->tax_regime ?? 'new';

        // Calculate both regimes if declaration exists
        $newRegimeTax = $this->calculateNewRegime($annualGross);
        $oldRegimeTax = 0;
        $oldRegimeDeductions = 0;

        if ($regime === 'old' && $declaration) {
            // Gather old regime deductions from declaration
            $oldRegimeDeductions = $this->getOldRegimeDeductions($declaration, $empSalary, $annualGross);
            $oldRegimeTax = $this->calculateOldRegime($annualGross, $oldRegimeDeductions);
        }

        // Use chosen regime
        if ($regime === 'old') {
            $annualTax = $oldRegimeTax;
            $taxableIncome = max($annualGross - $oldRegimeDeductions, 0);
        } else {
            $annualTax = $newRegimeTax;
            $taxableIncome = max($annualGross - self::STANDARD_DEDUCTION, 0);
        }

        $monthlyTds = $annualTax > 0 ? round($annualTax / 12, 2) : 0;

        return [
            'annual_tax' => round($annualTax, 2),
            'monthly_tds' => $monthlyTds,
            'regime' => $regime,
            'annual_gross' => round($annualGross, 2),
            'taxable_income' => round($taxableIncome, 2),
            'standard_deduction' => $regime === 'new' ? self::STANDARD_DEDUCTION : 50000,
            'breakdown' => [
                'new_regime_tax' => round($newRegimeTax, 2),
                'old_regime_tax' => round($oldRegimeTax, 2),
                'old_regime_deductions' => round($oldRegimeDeductions, 2),
            ],
        ];
    }

    /**
     * New Tax Regime FY 2025-26 calculation.
     */
    public function calculateNewRegime(float $annualGross): float
    {
        $taxableIncome = $annualGross - self::STANDARD_DEDUCTION;

        if ($taxableIncome <= 0) {
            return 0;
        }

        // Rebate u/s 87A
        if ($taxableIncome <= self::REBATE_LIMIT) {
            return 0;
        }

        $tax = 0;
        $remaining = $taxableIncome;

        // Slabs (top-down)
        $slabs = [
            [2400000, 0.30],
            [2000000, 0.25],
            [1600000, 0.20],
            [1200000, 0.15],
            [800000,  0.10],
            [400000,  0.05],
        ];

        foreach ($slabs as [$limit, $rate]) {
            if ($remaining > $limit) {
                $tax += ($remaining - $limit) * $rate;
                $remaining = $limit;
            }
        }

        // Health & Education Cess (4%)
        $cess = $tax * self::CESS_RATE;

        return round($tax + $cess);
    }

    /**
     * Old Tax Regime calculation.
     */
    public function calculateOldRegime(float $annualGross, float $deductions): float
    {
        $taxableIncome = max($annualGross - $deductions, 0);

        if ($taxableIncome <= 250000) {
            return 0;
        }

        // Rebate u/s 87A for old regime (income ≤ 5L)
        $tax = 0;
        if ($taxableIncome <= 500000) {
            $tax = ($taxableIncome - 250000) * 0.05;
            if ($tax <= 12500) return 0; // rebate
        } elseif ($taxableIncome <= 1000000) {
            $tax = 12500 + ($taxableIncome - 500000) * 0.20;
        } else {
            $tax = 112500 + ($taxableIncome - 1000000) * 0.30;
        }

        $cess = $tax * self::CESS_RATE;
        return round($tax + $cess);
    }

    /**
     * Get total deductions for old regime from tax declaration.
     */
    /**
     * Get total deductions for old regime from tax declaration.
     * Public alias for use from controllers.
     */
    public function getOldRegimeDeductionsPublic(TaxDeclaration $declaration, ?EmployeeSalary $empSalary, float $annualGross): float
    {
        return $this->getOldRegimeDeductions($declaration, $empSalary, $annualGross);
    }

    protected function getOldRegimeDeductions(TaxDeclaration $declaration, ?EmployeeSalary $empSalary, float $annualGross): float
    {
        $deductions = 50000; // Standard deduction (old regime)

        // Section 80C from investment_details table
        $investments80c = \App\Models\InvestmentDetail::where('tax_declaration_id', $declaration->id)
            ->where('section_code', '80C')
            ->sum('amount');
        $deductions += min((float) $investments80c, 150000); // 80C cap ₹1.5L

        // Section 80D from exemption_details table
        $exemptions80d = \App\Models\ExemptionDetail::where('tax_declaration_id', $declaration->id)
            ->where('section_code', '80D')
            ->sum('amount');
        $deductions += min((float) $exemptions80d, 100000); // 80D cap ₹1L

        // Other exemptions (NPS 80CCD etc.)
        $otherExemptions = \App\Models\ExemptionDetail::where('tax_declaration_id', $declaration->id)
            ->where('section_code', '!=', '80D')
            ->sum('amount');
        $deductions += (float) $otherExemptions;

        // HRA exemption (if renting)
        if ($declaration->is_rented_house && $declaration->rent_paid > 0) {
            $basicAnnual = $annualGross * (($empSalary->basic_percentage ?? 50) / 100);
            $hraAnnual = $basicAnnual * 0.50;
            $hra40 = $basicAnnual * 0.40; // 40% for non-metro, 50% for metro
            $actualRentMinusBasic10 = max(($declaration->rent_paid * 12) - ($basicAnnual * 0.10), 0);
            $hraExemption = min($hraAnnual, $hra40, $actualRentMinusBasic10);
            $deductions += $hraExemption;
        }

        // Home loan interest (Section 24b, cap ₹2L)
        if ($declaration->is_home_loan && $declaration->home_loan_interest > 0) {
            $deductions += min((float) $declaration->home_loan_interest, 200000);
        }

        return $deductions;
    }

    /**
     * Convert CTC to Gross (remove employer contributions).
     * Gross = CTC − Employer PF − Gratuity − ESIC Employer
     */
    /**
     * Convert CTC to taxable gross.
     * Gross = CTC − Employer PF (actually deposited to EPF account)
     * Gratuity is NOT deducted — it's a provision, not paid to employee.
     */
    public function ctcToGross(float $ctc, float $basicPct = 50): float
    {
        $basicAnnual = round($ctc * ($basicPct / 100));
        $pfBase = min($basicAnnual / 12, SalaryCalculator::PF_BASIC_CAP_MONTHLY);
        $pfEmployerAnnual = round($pfBase * SalaryCalculator::PF_RATE) * 12;
        return round($ctc - $pfEmployerAnnual);
    }

    protected function zeroResult(string $regime): array
    {
        return [
            'annual_tax' => 0,
            'monthly_tds' => 0,
            'regime' => $regime,
            'annual_gross' => 0,
            'taxable_income' => 0,
            'standard_deduction' => 0,
            'breakdown' => [
                'new_regime_tax' => 0,
                'old_regime_tax' => 0,
                'old_regime_deductions' => 0,
            ],
        ];
    }
}
