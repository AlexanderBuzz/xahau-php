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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * One validator's contribution to the random number generator for hooks
 * (amendment RNG).
 *
 * A pseudo transaction: each validator on the UNLReport commits to a secret
 * with NextRandomDigest and reveals it as RandomData one ledger later, at the
 * start of the ledger. The model is here to read it back.
 *
 * https://github.com/Xahau/xahaud/releases/tag/2026.10.13-release%2B4119
 */
class Entropy extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'LedgerSequence' => UnsignedInt32::class,
        'PublicKey' => Blob::class,
        'NextRandomDigest' => Hash256::class,
        'RandomData' => Hash256::class,
    ];
}
