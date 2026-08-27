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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * The alarm going off: the validators emit this when a schedule set by
 * CronSet comes due, and it invokes the owner's hooks.
 *
 * A pseudo transaction - no client submits it, and it carries no signature.
 * Owner is the account whose schedule fired, LedgerSequence the ledger it
 * fired in. The model is here so that it can be read back out of a ledger.
 *
 * Cron is a ledger entry type as well (65), where the schedule itself lives
 * with StartTime, DelaySeconds and RepeatCount.
 *
 * The Xahau documentation has no page on Cron yet, so the reference here is
 * the node itself:
 * https://github.com/Xahau/xahaud/blob/dev/include/xrpl/protocol/detail/transactions.macro
 */
class Cron extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'Owner' => AccountId::class,
        'LedgerSequence' => UnsignedInt32::class,
    ];
}
