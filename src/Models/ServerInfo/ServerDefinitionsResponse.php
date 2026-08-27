<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Models\ServerInfo;

use Exception;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\BaseResponse;

/**
 * The answer to ServerDefinitionsRequest.
 */
class ServerDefinitionsResponse extends BaseResponse
{
    /**
     * The definitions themselves, ready to be handed to a codec, a wallet or
     * Xahau::useDefinitions().
     *
     * The node answers with the tables plus the usual JSON-RPC noise - a hash
     * and a status - which Definitions ignores.
     *
     * @return Definitions
     * @throws Exception
     */
    public function getDefinitions(): Definitions
    {
        return Definitions::fromArray($this->getResult());
    }
}
