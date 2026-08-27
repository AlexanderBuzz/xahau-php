<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypes;

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\CronSet;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://github.com/Xahau/xahaud/blob/dev/include/xrpl/protocol/detail/transactions.macro
 */
final class CronSetTest extends TransactionTypeTestCase
{
    /** Seconds since the Ripple epoch, 2000-01-01. */
    private const START_TIME = 800000000;

    private const ONE_DAY = 86400;

    protected static function modelClass(): string
    {
        return CronSet::class;
    }

    public static function transactions(): array
    {
        return [
            'a one-off alarm' => [self::common([
                'TransactionType' => 'CronSet',
                'StartTime' => self::START_TIME,
            ])],
            'a daily alarm, thirty times' => [self::common([
                'TransactionType' => 'CronSet',
                'StartTime' => self::START_TIME,
                'DelaySeconds' => self::ONE_DAY,
                'RepeatCount' => 30,
            ])],
            // With tfCronUnset the transaction clears the schedule, and then
            // carries none of the three fields - which is why the format has
            // them all optional even though StartTime is required otherwise.
            'clearing the schedule' => [self::common([
                'TransactionType' => 'CronSet',
                'Flags' => CronSet::TF_CRON_UNSET,
            ])],
        ];
    }
}
