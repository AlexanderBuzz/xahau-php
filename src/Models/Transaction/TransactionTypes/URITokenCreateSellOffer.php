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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * Offers a URIToken for sale, to everyone or to one account.
 *
 * A URIToken carries its offer itself rather than creating an offer object, so
 * there is only ever one outstanding offer per token.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokencreateselloffer
 */
class URITokenCreateSellOffer extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'URITokenID' => Hash256::class,
        'Amount' => Amount::class,
        'Destination' => AccountId::class,
    ];
}
