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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * Attaches arbitrary key/value remarks to a ledger object.
 *
 * https://docs.xahau.network/features/transaction-types/setremarks
 */
class SetRemarks extends XahauTransaction
{
    protected array $transactionTypeProperties = [
        'ObjectID' => Hash256::class,
        'Remarks' => StArray::class,
    ];
}
