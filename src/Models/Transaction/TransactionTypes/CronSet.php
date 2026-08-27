<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes;

use Hardcastle\Xahau_PHP\Models\Transaction\XahauTransaction;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * Schedules an alarm that later fires the account's own hooks, or clears one.
 *
 * StartTime is when the alarm first goes off, in seconds since the Ripple
 * epoch. RepeatCount and DelaySeconds together make it recurring: the alarm is
 * rearmed DelaySeconds later, RepeatCount more times. Both or neither - a
 * repeat count without an interval says nothing, and an interval without a
 * count would run forever.
 *
 * Sent with tfCronUnset the transaction removes the schedule instead, and then
 * carries none of the three fields.
 *
 * All three are optional in the transaction format because of that flag; the
 * node requires StartTime in every other case. Cron ledger objects, by
 * contrast, always carry all three.
 *
 * The Xahau documentation has no page on Cron yet, so the reference here is
 * the node itself:
 * https://github.com/Xahau/xahaud/blob/dev/include/xrpl/protocol/detail/transactions.macro
 */
class CronSet extends XahauTransaction
{
    /** Removes the schedule rather than setting one. */
    public const TF_CRON_UNSET = 0x00000001;

    protected array $transactionTypeProperties = [
        'DelaySeconds' => UnsignedInt32::class,
        'RepeatCount' => UnsignedInt32::class,
        'StartTime' => UnsignedInt32::class,
    ];
}
