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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * Withdraws the sell offer on a URIToken.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokencancelselloffer
 */
class URITokenCancelSellOffer extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'URITokenID' => Hash256::class,
    ];
}
