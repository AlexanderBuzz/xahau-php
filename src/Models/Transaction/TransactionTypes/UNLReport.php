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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * Reports the active validators and import validator list keys of a flag
 * ledger.
 *
 * A pseudo transaction: the network writes it, no client submits it. The model
 * is here to read it back.
 *
 * Note the two adjacent object fields - this is the shape that made the
 * missing ObjectEndMarker in xrpl_php's encoder visible.
 *
 * https://docs.xahau.network/features/network/governance
 */
class UNLReport extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'LedgerSequence' => UnsignedInt32::class,
        'ActiveValidator' => StObject::class,
        'ImportVLKey' => StObject::class,
    ];
}
