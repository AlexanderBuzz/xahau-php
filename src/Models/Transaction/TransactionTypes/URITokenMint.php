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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * Creates a URIToken, Xahau's single-object answer to an NFT.
 *
 * Giving Amount puts the token up for sale in the same transaction, and
 * Destination restricts who may buy it. Digest commits to the content the URI
 * points at, so that the reference cannot be swapped afterwards.
 *
 * Ordinal 45, which the XRP Ledger gives to XChainAddClaimAttestation - the
 * collision this package exists to keep apart.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenmint
 */
class URITokenMint extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'URI' => Blob::class,
        'Digest' => Hash256::class,
        'Amount' => Amount::class,
        'Destination' => AccountId::class,
    ];
}
