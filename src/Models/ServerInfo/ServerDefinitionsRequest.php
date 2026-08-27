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

use Hardcastle\XRPL_PHP\Models\BaseRequest;

/**
 * The node's own definitions: the field, type and transaction tables the
 * binary codec runs on.
 *
 * This is where definitions/definitions.json comes from, and asking for it at
 * runtime is what lets an application follow an amendment without waiting for
 * a release here - the point of Xahau::useDefinitions().
 *
 * https://xrpl.org/server_definitions.html
 */
class ServerDefinitionsRequest extends BaseRequest
{
    protected string $command = 'server_definitions';
}
