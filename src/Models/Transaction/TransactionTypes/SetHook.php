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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * Installs, updates or removes the hooks on an account.
 *
 * Hooks is an array of Hook objects, each of which may carry HookParameters
 * and HookGrants of its own - the deepest structure of any Xahau transaction,
 * and the reason the codec has to keep the definitions through two levels of
 * nesting.
 *
 * The code a hook runs is passed as CreateCode; a hook already on the ledger is
 * referenced by HookHash instead. HookOn, HookOnIncoming, HookOnOutgoing and
 * HookCanEmit are 256 bit masks over the transaction types, not amounts.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/sethook
 */
class SetHook extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'Hooks' => StArray::class,
    ];
}
