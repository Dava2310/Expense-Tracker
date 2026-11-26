<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Throwable;

/**
 * A period of time that limits the incomes or expenses list, read from the query string.
 */
class PeriodFilter
{
    /**
     * The periods that can be selected.
     *
     * @var array<string, string>
     */
    public const PERIODS = [
        'all' => 'All time',
        '3m' => 'Last 3 months',
        'month' => 'This month',
        '6m' => 'Last 6 months',
        'year' => 'This year',
        'custom' => 'Custom range',
    ];

    private const DEFAULT_PERIOD = 'all';

    private function __construct(
        public readonly string $name,
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $to,
    ) {}

    /**
     * Reads the period from the request, ignoring anything that is not valid.
     */
    public static function fromRequest(Request $request): self
    {
        $requestedPeriod = $request->query('period');
        $name = is_string($requestedPeriod) && isset(self::PERIODS[$requestedPeriod])
            ? $requestedPeriod
            : self::DEFAULT_PERIOD;

        $from = self::parseDate($request->query('from'));
        $to = self::parseDate($request->query('to'));

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self($name, $from, $to);
    }

    /**
     * Whether a period other than the default one is selected.
     */
    public function isActive(): bool
    {
        return $this->name !== self::DEFAULT_PERIOD;
    }

    /**
     * The first moment included in the period, if it has a lower limit.
     */
    public function start(): ?CarbonInterface
    {
        return match ($this->name) {
            '3m' => now()->subMonths(2)->startOfMonth(),
            'month' => now()->startOfMonth(),
            '6m' => now()->subMonths(5)->startOfMonth(),
            'year' => now()->startOfYear(),
            'custom' => $this->from?->startOfDay(),
            default => null,
        };
    }

    /**
     * The last moment included in the period, if it has an upper limit.
     */
    public function end(): ?CarbonInterface
    {
        return $this->name === 'custom' ? $this->to?->endOfDay() : null;
    }

    /**
     * Describes the period for the page subtitle ("received {phrase} ...").
     */
    public function phrase(): string
    {
        return match ($this->name) {
            '3m' => 'in the last 3 months',
            'month' => 'this month',
            '6m' => 'in the last 6 months',
            'year' => 'this year',
            'custom' => match (true) {
                $this->from !== null && $this->to !== null => 'between '.$this->from->format('M j, Y').' and '.$this->to->format('M j, Y'),
                $this->from !== null => 'since '.$this->from->format('M j, Y'),
                $this->to !== null => 'until '.$this->to->format('M j, Y'),
                default => 'in total',
            },
            default => 'in total',
        };
    }

    /**
     * Reads a Y-m-d date from the query string, ignoring anything that is not a real date.
     */
    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
