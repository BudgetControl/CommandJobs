<?php

namespace Budgetcontrol\jobs\Domain\Planning;

use DateTimeImmutable;
use InvalidArgumentException;

class Recurrence
{
    /** @return array{0: DateTimeImmutable[], 1: DateTimeImmutable} */
    public static function occurrences(DateTimeImmutable $next, string $period, DateTimeImmutable $through, ?DateTimeImmutable $end = null): array
    {
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            throw new InvalidArgumentException("Unsupported planned entry period: {$period}");
        }

        $dates = [];
        while ($next <= $through && ($end === null || $next->format('Y-m-d') <= $end->format('Y-m-d'))) {
            $dates[] = $next;
            $next = self::advance($next, $period);
        }

        return [$dates, $next];
    }

    private static function advance(DateTimeImmutable $date, string $period): DateTimeImmutable
    {
        if ($period === 'daily' || $period === 'weekly') {
            return $date->modify($period === 'daily' ? '+1 day' : '+1 week');
        }

        $day = (int) $date->format('j');
        $first = $date->modify('first day of this month')->modify($period === 'monthly' ? '+1 month' : '+1 year');
        return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), min($day, (int) $first->format('t')));
    }
}
