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

/**
 * Brings an account, or a balance, over from the XRP Ledger.
 *
 * Blob is a proof built from a transaction on the source network together with
 * the validator manifests that signed it, which is why it is the only required
 * field - everything else is read out of the proof.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/import
 */
class Import extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'Blob' => Blob::class,
        'Issuer' => AccountId::class,
    ];
}
