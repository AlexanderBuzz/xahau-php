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

use Hardcastle\Xahau_PHP\Client\XahauFaucet;
use PHPUnit\Framework\Attributes\Group;

/**
 * The faucet, which xrpl_php on its own cannot find.
 *
 * It does not ask for another wallet: the run already has one, and the public
 * faucets allow a client one request every 45 seconds. That the wallet exists
 * and has a balance is the proof that the funding worked.
 */
#[Group('integration')]
final class FundWalletTest extends XahauIntegrationTestCase
{
    public function testTheRunGotAFundedWallet(): void
    {
        $balance = self::client()->getXrpBalance(self::primaryWallet()->getAddress());

        $this->assertGreaterThan(0.0, (float)$balance);
    }

    public function testTheFaucetOfThisNetworkIsKnown(): void
    {
        $this->assertSame(
            XahauFaucet::TESTNET_HOST,
            self::client()->getFaucet()->faucetHost()
        );
    }
}
