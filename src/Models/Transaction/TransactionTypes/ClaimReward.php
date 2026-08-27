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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Issue;

/**
 * Claims the balance reward, or opts out of it.
 *
 * Sent without flags it enrols the account and pays out what has accrued;
 * sent with tfOptOut (0x00000001) it leaves the scheme. Issuer names the
 * account whose reward hook is addressed, which on the public network is the
 * genesis account.
 *
 * https://docs.xahau.network/features/network/balance-rewards
 */
class ClaimReward extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'Issuer' => AccountId::class,
        'ClaimCurrency' => Issue::class,
    ];
}
