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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Cron;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * The alarm going off. A pseudo transaction: the validators emit it, no client
 * submits it, so the model exists to read it back out of a ledger.
 *
 * https://github.com/Xahau/xahaud/blob/dev/include/xrpl/protocol/detail/transactions.macro
 */
final class CronTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return Cron::class;
    }

    public static function transactions(): array
    {
        return [
            'an alarm that came due' => [self::common([
                'TransactionType' => 'Cron',
                'Owner' => self::DESTINATION,
                'LedgerSequence' => 1234560,
            ])],
        ];
    }
}
