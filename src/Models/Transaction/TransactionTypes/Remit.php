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

use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Vector256;

/**
 * Sends XRP, tokens and URITokens in one transaction, creating the destination
 * account if it does not exist yet.
 *
 * https://docs.xahau.network/features/transaction-types/remit
 */
class Remit extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Destination' => AccountId::class,
        'DestinationTag' => UnsignedInt32::class,
        'Amounts' => StArray::class,
        'MintURIToken' => StObject::class,
        'URITokenIDs' => Vector256::class,
        'InvoiceID' => Hash256::class,
        'Inform' => AccountId::class,
        'Blob' => Blob::class,
    ];
}
