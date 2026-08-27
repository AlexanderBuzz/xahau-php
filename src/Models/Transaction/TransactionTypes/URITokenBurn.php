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
 * Destroys a URIToken. The issuer may burn it only if it was minted burnable.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenburn
 */
class URITokenBurn extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'URITokenID' => Hash256::class,
    ];
}
