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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * Triggers a hook without moving anything.
 *
 * Every field is optional: the transaction exists to be a message. Destination
 * decides whose hooks run besides the sender's own, and Blob carries whatever
 * the hook expects to read.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/invoke
 */
class Invoke extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'Blob' => Blob::class,
        'Destination' => AccountId::class,
        'InvoiceID' => Hash256::class,
        'DestinationTag' => UnsignedInt32::class,
    ];
}
