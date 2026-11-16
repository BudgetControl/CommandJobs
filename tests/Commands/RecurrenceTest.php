<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Domain\Planning\Recurrence;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RecurrenceTest extends TestCase
{
    public function testYearlyDoesNotRepeatInTheSameMonthNextYear(): void
    {
        [$dates, $next] = Recurrence::occurrences(new DateTimeImmutable('2026-11-15 10:30:00'), 'yearly', new DateTimeImmutable('2026-11-30 23:59:59'));
        self::assertSame(['2026-11-15'], array_map(fn ($date) => $date->format('Y-m-d'), $dates));
        self::assertSame('2027-11-15 10:30:00', $next->format('Y-m-d H:i:s'));
        self::assertSame([], Recurrence::occurrences($next, 'yearly', new DateTimeImmutable('2026-11-30 23:59:59'))[0]);
    }

    public function testDailyAndWeeklyRespectStartAndAdvancePastMonth(): void
    {
        [$daily, $nextDaily] = Recurrence::occurrences(new DateTimeImmutable('2026-02-27'), 'daily', new DateTimeImmutable('2026-02-28 23:59:59'));
        self::assertSame(['2026-02-27', '2026-02-28'], array_map(fn ($date) => $date->format('Y-m-d'), $daily));
        self::assertSame('2026-03-01', $nextDaily->format('Y-m-d'));
        [$weekly, $nextWeekly] = Recurrence::occurrences(new DateTimeImmutable('2026-12-20'), 'weekly', new DateTimeImmutable('2026-12-31 23:59:59'));
        self::assertSame(['2026-12-20', '2026-12-27'], array_map(fn ($date) => $date->format('Y-m-d'), $weekly));
        self::assertSame('2027-01-03', $nextWeekly->format('Y-m-d'));
    }

    public function testEndDateIsInclusiveAndMonthlyDatesDoNotOverflow(): void
    {
        [$dates, $next] = Recurrence::occurrences(new DateTimeImmutable('2026-01-31 12:00:00'), 'monthly', new DateTimeImmutable('2026-04-30 23:59:59'), new DateTimeImmutable('2026-02-28'));
        self::assertSame(['2026-01-31', '2026-02-28'], array_map(fn ($date) => $date->format('Y-m-d'), $dates));
        self::assertSame('2026-03-28', $next->format('Y-m-d'));
    }

    public function testUnknownPeriodFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Recurrence::occurrences(new DateTimeImmutable('2026-01-01'), 'unknown', new DateTimeImmutable('2026-01-31'));
    }
}
