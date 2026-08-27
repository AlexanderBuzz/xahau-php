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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * Accepts a sell offer on a URIToken.
 *
 * Amount is what the buyer is willing to pay and has to match the offer; it is
 * required, so that a change of price cannot be paid for unnoticed.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenbuy
 */
class URITokenBuy extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'URITokenID' => Hash256::class,
        'Amount' => Amount::class,
    ];
}
