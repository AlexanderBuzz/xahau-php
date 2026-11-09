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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash128;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt16;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt8;

/**
 * Changes the settings of an account.
 *
 * The XRP Ledger has this type too, but xrpl_php's model refuses the two fields
 * only Xahau has. HookStateScale raises how much hook state the account may
 * keep. AppLoader (amendment PWALoader) publishes the HTML bootloader of a
 * Progressive Web App on the ledger: valid UTF-8, up to 4096 bytes, hex
 * encoded, one drop of fee per byte and no reserve. An empty value removes it.
 *
 * https://github.com/Xahau/xahaud/releases/tag/2026.10.13-release%2B4119
 */
class AccountSet extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'ClearFlag' => UnsignedInt32::class,
        'Domain' => Blob::class,
        'EmailHash' => Hash128::class,
        'MessageKey' => Blob::class,
        'NFTokenMinter' => AccountId::class,
        'SetFlag' => UnsignedInt32::class,
        'TransferRate' => UnsignedInt32::class,
        'TickSize' => UnsignedInt8::class,
        'WalletLocator' => Hash256::class,
        'WalletSize' => UnsignedInt32::class,
        'HookStateScale' => UnsignedInt16::class,
        'AppLoader' => Blob::class,
    ];
}
