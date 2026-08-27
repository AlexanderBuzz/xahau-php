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
 * Mints XAH and sets governance flags, from the genesis account only.
 *
 * A client never submits this: it is emitted by the governance hook on the
 * genesis account. The model exists so that the transaction can be read back
 * out of a ledger.
 *
 * https://docs.xahau.network/features/network/governance
 */
class GenesisMint extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'GenesisMints' => StArray::class,
    ];
}
