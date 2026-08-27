<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Models\Transaction;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;

/**
 * The common fields of a Xahau transaction.
 *
 * The two networks disagree here as well, not only on the ordinals. Xahau's
 * TxFormats gives every transaction six fields the XRP Ledger does not have,
 * and does not have the one the XRP Ledger added for account permission
 * delegation:
 *
 * - HookParameters and HookName reach the hooks a transaction triggers, on any
 *   transaction, which is why they are common rather than part of SetHook.
 * - EmitDetails and FirstLedgerSequence appear on transactions a hook emitted;
 *   a client does not set them, but it does read them back.
 * - OperationLimit and PreviousTxnID are inherited from rippled's own common
 *   set, which Xahau kept and the XRP Ledger dropped.
 *
 * The list is adjusted rather than restated, so that a common field added to
 * xrpl_php's BaseTransaction arrives here too instead of being silently
 * dropped by a copy that has gone stale.
 *
 * https://github.com/Xahau/xahaud/blob/dev/src/libxrpl/protocol/TxFormats.cpp
 */
abstract class XahauTransaction extends BaseTransaction
{
    public function __construct(array $properties)
    {
        unset($this->baseProperties['Delegate']);

        $this->baseProperties += [
            'EmitDetails' => StObject::class,
            'FirstLedgerSequence' => UnsignedInt32::class,
            'HookParameters' => StArray::class,
            'HookName' => Blob::class,
            'OperationLimit' => UnsignedInt32::class,
            'PreviousTxnID' => Hash256::class,
        ];

        parent::__construct($properties);
    }
}
