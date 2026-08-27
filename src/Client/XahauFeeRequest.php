<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Client;

use Hardcastle\XRPL_PHP\Models\ServerInfo\FeeRequest;

/**
 * The `fee` command with a transaction attached.
 *
 * Xahau extends `fee` to take a tx_blob and answer with what that particular
 * transaction costs, hooks included. Without one it falls back to a base fee
 * of 10 drops, which is the same answer the XRP Ledger gives and is wrong for
 * anything that sets a hook off.
 *
 * BaseRequest turns its public properties into the params of the call and
 * converts their names to snake_case, so txBlob is sent as tx_blob and nothing
 * in xrpl_php has to know about this.
 *
 * https://docs.xahau.network/features/transaction-signing/transaction-fees
 */
class XahauFeeRequest extends FeeRequest
{
    public function __construct(public ?string $txBlob = null)
    {
    }
}
