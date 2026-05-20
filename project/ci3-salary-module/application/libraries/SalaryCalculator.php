<?php defined('BASEPATH') OR exit('No direct script access allowed');

class SalaryCalculator
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Calculate annual and monthly salary breakdown.
     *
     * @param array $input [
     *   ctc => float,
     *   basic_percentage => float,
     *   is_pf_enabled => int|bool,
     *   is_esic_enabled => int|bool
     * ]
     * @param array $components ordered component list
     * @return array
     */
    public function calculate(array $input, array $components)
    {
        $ctc = (float) ($input['ctc'] ?? 0);
        $basicPercentage = (float) ($input['basic_percentage'] ?? 50);
        $pfEnabled = !empty($input['is_pf_enabled']) ? 1 : 0;
        $esicEnabled = !empty($input['is_esic_enabled']) ? 1 : 0;

        $context = [
            'CTC_ANNUAL' => $ctc,
            'CTC_MONTHLY' => $ctc / 12,
            'BASIC_PERCENTAGE' => $basicPercentage,
            'PF_ENABLED' => $pfEnabled,
            'ESIC_ENABLED' => $esicEnabled,
        ];

        $breakdown = [];
        $earnings = 0.0;
        $deductions = 0.0;
        $employer = 0.0;

        foreach ($components as $component) {
            if ((int) $component['status'] !== 1) {
                continue;
            }

            $name = trim($component['name']);
            $var = $this->toVar($name);

            if (!$this->passesCondition($component['condition_rule'], $context)) {
                $amount = 0.0;
            } else {
                $amount = $this->resolveAmount($component, $context, $ctc, $basicPercentage);
            }

            $amount = round(max($amount, 0), 2);
            $breakdown[$var] = $amount;
            $context[$var] = $amount;

            if ($component['type'] === 'earning') {
                $earnings += $amount;
            } elseif ($component['type'] === 'deduction') {
                $deductions += $amount;
            } elseif ($component['type'] === 'employer') {
                $employer += $amount;
            }
        }

        // Ensure Gross exists; if not present in setup, build from earning sum.
        if (!isset($breakdown['GROSS'])) {
            $breakdown['GROSS'] = round($earnings, 2);
            $context['GROSS'] = $breakdown['GROSS'];
        }

        $netAnnual = $breakdown['GROSS'] - $deductions;
        $response = [
            'annual' => $this->mapStandardOutput($breakdown, $netAnnual),
            'monthly' => $this->toMonthlyOutput($breakdown, $netAnnual),
            'meta' => [
                'ctc_annual' => round($ctc, 2),
                'ctc_monthly' => round($ctc / 12, 2),
                'basic_percentage' => $basicPercentage,
                'total_earnings_annual' => round($earnings, 2),
                'total_deductions_annual' => round($deductions, 2),
                'total_employer_contribution_annual' => round($employer, 2),
            ],
        ];

        return $response;
    }

    protected function resolveAmount(array $component, array $context, $ctc, $basicPercentage)
    {
        $type = $component['calculation_type'];
        $value = (float) $component['value'];
        $formula = trim((string) $component['formula']);

        if ($type === 'fixed') {
            return $value;
        }

        if ($type === 'percentage') {
            // Percentage component uses formula as base variable/expression.
            if ($formula !== '') {
                $base = $this->evaluateExpression($formula, $context);
            } else {
                $base = $ctc;
            }

            // For Basic, prefer employee configured basic percentage.
            if ($this->toVar($component['name']) === 'BASIC') {
                return $base * ($basicPercentage / 100);
            }

            return $base * ($value / 100);
        }

        if ($type === 'formula') {
            return $this->evaluateExpression($formula, $context);
        }

        return 0.0;
    }

    protected function passesCondition($conditionRule, array $context)
    {
        $conditionRule = trim((string) $conditionRule);
        if ($conditionRule === '') {
            return true;
        }

        try {
            return (bool) $this->evaluateExpression($conditionRule, $context);
        } catch (Exception $e) {
            // Fail-safe: if condition parsing fails, ignore component.
            return false;
        }
    }

    protected function mapStandardOutput(array $breakdown, $netAnnual)
    {
        return [
            'basic' => (float) ($breakdown['BASIC'] ?? 0),
            'hra' => (float) ($breakdown['HRA'] ?? 0),
            'conveyance' => (float) ($breakdown['CONVEYANCE'] ?? 0),
            'medical' => (float) ($breakdown['MEDICAL'] ?? 0),
            'special' => (float) ($breakdown['SPECIAL_ALLOWANCE'] ?? 0),
            'gross' => (float) ($breakdown['GROSS'] ?? 0),
            'pf' => (float) ($breakdown['PF_EMPLOYEE'] ?? 0),
            'esic_employee' => (float) ($breakdown['ESIC_EMPLOYEE'] ?? 0),
            'esic_employer' => (float) ($breakdown['ESIC_EMPLOYER'] ?? 0),
            'gratuity' => (float) ($breakdown['GRATUITY'] ?? 0),
            'net_salary' => round((float) $netAnnual, 2),
            'components' => $breakdown,
        ];
    }

    protected function toMonthlyOutput(array $breakdown, $netAnnual)
    {
        $monthly = [];
        foreach ($this->mapStandardOutput($breakdown, $netAnnual) as $key => $val) {
            if ($key === 'components') {
                $monthly['components'] = [];
                foreach ($val as $k => $amount) {
                    $monthly['components'][strtolower($k)] = round($amount / 12, 2);
                }
                continue;
            }
            $monthly[$key] = round($val / 12, 2);
        }
        return $monthly;
    }

    protected function toVar($name)
    {
        $v = strtoupper(trim((string) $name));
        $v = preg_replace('/[^A-Z0-9]+/', '_', $v);
        return trim($v, '_');
    }

    /**
     * Safe expression evaluator (no eval).
     * Supports: + - * /, parentheses, comparison, AND/OR, MIN/MAX/ROUND, IF.
     */
    protected function evaluateExpression($expression, array $context)
    {
        $tokens = $this->tokenize($expression);
        $rpn = $this->toRpn($tokens);
        return $this->evalRpn($rpn, $context);
    }

    protected function tokenize($expression)
    {
        $pattern = '/\s*('
            . '\d+\.\d+|\d+'
            . '|==|!=|<=|>=|<|>'
            . '|\bAND\b|\bOR\b'
            . '|[A-Za-z_][A-Za-z0-9_]*'
            . '|[\+\-\*\/\(\),]'
            . ')\s*/i';

        preg_match_all($pattern, $expression, $matches);
        $tokens = $matches[1];
        if (empty($tokens)) {
            throw new Exception('Invalid expression: ' . $expression);
        }
        return $tokens;
    }

    protected function toRpn(array $tokens)
    {
        $output = [];
        $ops = [];
        $argCountStack = [];

        $precedence = [
            'OR' => 1,
            'AND' => 2,
            '==' => 3, '!=' => 3, '<' => 3, '<=' => 3, '>' => 3, '>=' => 3,
            '+' => 4, '-' => 4,
            '*' => 5, '/' => 5,
        ];

        $isFunction = function ($token) {
            $u = strtoupper($token);
            return in_array($u, ['MIN', 'MAX', 'ROUND', 'IF'], true);
        };

        foreach ($tokens as $i => $token) {
            $upper = strtoupper($token);
            $next = $tokens[$i + 1] ?? null;

            if (is_numeric($token)) {
                $output[] = (float) $token;
                continue;
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $token) && !$isFunction($token)) {
                $output[] = ['var' => strtoupper($token)];
                continue;
            }

            if ($isFunction($token) && $next === '(') {
                $ops[] = strtoupper($token);
                continue;
            }

            if ($token === ',') {
                while (!empty($ops) && end($ops) !== '(') {
                    $output[] = array_pop($ops);
                }
                if (!empty($argCountStack)) {
                    $argCountStack[count($argCountStack) - 1]++;
                }
                continue;
            }

            if ($token === '(') {
                $ops[] = '(';
                $prev = $tokens[$i - 1] ?? null;
                if ($prev && $isFunction($prev)) {
                    $argCountStack[] = 1;
                }
                continue;
            }

            if ($token === ')') {
                while (!empty($ops) && end($ops) !== '(') {
                    $output[] = array_pop($ops);
                }
                if (empty($ops)) {
                    throw new Exception('Mismatched parentheses');
                }
                array_pop($ops); // pop "("

                if (!empty($ops) && $isFunction(end($ops))) {
                    $fn = array_pop($ops);
                    $argc = array_pop($argCountStack);
                    $output[] = ['fn' => $fn, 'argc' => (int) $argc];
                }
                continue;
            }

            if (isset($precedence[$upper])) {
                while (!empty($ops)) {
                    $top = end($ops);
                    $topUpper = strtoupper((string) $top);
                    if (!isset($precedence[$topUpper])) {
                        break;
                    }
                    if ($precedence[$topUpper] >= $precedence[$upper]) {
                        $output[] = array_pop($ops);
                    } else {
                        break;
                    }
                }
                $ops[] = $upper;
            }
        }

        while (!empty($ops)) {
            $op = array_pop($ops);
            if ($op === '(' || $op === ')') {
                throw new Exception('Mismatched parentheses');
            }
            $output[] = $op;
        }

        return $output;
    }

    protected function evalRpn(array $rpn, array $context)
    {
        $stack = [];

        foreach ($rpn as $token) {
            if (is_float($token) || is_int($token)) {
                $stack[] = (float) $token;
                continue;
            }

            if (is_array($token) && isset($token['var'])) {
                $stack[] = (float) ($context[$token['var']] ?? 0);
                continue;
            }

            if (is_array($token) && isset($token['fn'])) {
                $argc = (int) $token['argc'];
                $args = [];
                for ($i = 0; $i < $argc; $i++) {
                    array_unshift($args, (float) array_pop($stack));
                }
                $stack[] = $this->applyFunction($token['fn'], $args);
                continue;
            }

            $b = (float) array_pop($stack);
            $a = (float) array_pop($stack);
            $stack[] = $this->applyOperator($token, $a, $b);
        }

        if (count($stack) !== 1) {
            throw new Exception('Invalid expression result');
        }

        return (float) $stack[0];
    }

    protected function applyFunction($fn, array $args)
    {
        switch (strtoupper($fn)) {
            case 'MIN':
                return min($args);
            case 'MAX':
                return max($args);
            case 'ROUND':
                $v = $args[0] ?? 0;
                $p = (int) ($args[1] ?? 2);
                return round($v, $p);
            case 'IF':
                $cond = !empty($args[0]);
                return $cond ? ($args[1] ?? 0) : ($args[2] ?? 0);
            default:
                throw new Exception('Unsupported function: ' . $fn);
        }
    }

    protected function applyOperator($op, $a, $b)
    {
        switch (strtoupper($op)) {
            case '+': return $a + $b;
            case '-': return $a - $b;
            case '*': return $a * $b;
            case '/': return $b == 0 ? 0 : $a / $b;
            case '<': return $a < $b ? 1 : 0;
            case '<=': return $a <= $b ? 1 : 0;
            case '>': return $a > $b ? 1 : 0;
            case '>=': return $a >= $b ? 1 : 0;
            case '==': return $a == $b ? 1 : 0;
            case '!=': return $a != $b ? 1 : 0;
            case 'AND': return (!empty($a) && !empty($b)) ? 1 : 0;
            case 'OR': return (!empty($a) || !empty($b)) ? 1 : 0;
            default:
                throw new Exception('Unsupported operator: ' . $op);
        }
    }
}
