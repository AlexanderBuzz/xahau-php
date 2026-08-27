<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Integration;

use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\Xahau_PHP\Models\ServerInfo\ServerDefinitionsRequest;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Does the node agree with what this package believes about it?
 */
#[Group('integration')]
final class ConnectionTest extends XahauIntegrationTestCase
{
    public function testTheNodeReportsTheNetworkWeThinkWeAreOn(): void
    {
        $info = self::request(new ServerInfoRequest())['info'];

        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $info['network_id'] ?? null);
    }

    /**
     * The bundled definitions against the node's own.
     *
     * This is the check that offline tests cannot make. definitions.json is a
     * capture, and a capture goes stale: an amendment adds a transaction type,
     * the ordinals shift, and everything here keeps encoding happily against
     * the set it was born with. Comparing the two says how far the capture has
     * drifted from the network it claims to describe.
     */
    public function testTheBundledDefinitionsMatchTheNodesOwn(): void
    {
        $live = self::request(new ServerDefinitionsRequest());

        $this->assertArrayHasKey('TRANSACTION_TYPES', $live);

        $bundled = json_decode(
            (string)file_get_contents(__DIR__ . '/../../definitions/definitions.json'),
            true
        );

        foreach ($bundled['TRANSACTION_TYPES'] as $name => $ordinal) {
            $this->assertSame(
                $ordinal,
                $live['TRANSACTION_TYPES'][$name] ?? null,
                "The node gives {$name} a different ordinal than definitions/definitions.json does. "
                . 'Refresh the capture; see definitions/README.md.'
            );
        }
    }

    /**
     * The types this package models have to exist on the network, or the
     * models describe something nobody will accept.
     */
    public function testTheNodeKnowsEveryTypeThisPackageModels(): void
    {
        $live = self::request(new ServerDefinitionsRequest())['TRANSACTION_TYPES'];

        $files = glob(__DIR__ . '/../../src/Models/Transaction/TransactionTypes/*.php');

        foreach ($files === false ? [] : $files as $file) {
            $type = basename($file, '.php');

            $this->assertArrayHasKey(
                $type,
                $live,
                "This package models {$type}, which the node does not know."
            );
        }
    }
}
