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

use Hardcastle\XRPL_PHP\Models\ServerInfo\FeeResponse;

/**
 * The answer to XahauFeeRequest.
 *
 * Empty on purpose, and required: JsonRpcClient picks the response class by
 * replacing Request with Response in the request's class name, so a request
 * without its counterpart in the same namespace has nothing to be read into.
 */
class XahauFeeResponse extends FeeResponse
{
}
