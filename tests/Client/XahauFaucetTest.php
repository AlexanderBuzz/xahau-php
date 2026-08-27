<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Client;

use Exception;
use Hardcastle\Xahau_PHP\Client\XahauFaucet;
use Hardcastle\Xahau_PHP\Xahau;
use PHPUnit\Framework\TestCase;

/**
 * Which faucet belongs to a connection.
 *
 * xrpl_php cannot answer this for Xahau: it looks for the substring 'testnet',
 * and the Xahau testnet is at xahau-test.net, which has 'test.net' in it but
 * not 'testnet'. It throws rather than guessing, which is why this class only
 * has to supply the host.
 */
final class XahauFaucetTest extends TestCase
{
    private function faucetFor(string $url): XahauFaucet
    {
        return Xahau::client($url, Xahau::TESTNET_NETWORK_ID)->getFaucet();
    }

    public function testTheTestnetFaucet(): void
    {
        $this->assertSame(
            XahauFaucet::TESTNET_HOST,
            $this->faucetFor(Xahau::TESTNET)->faucetHost()
        );
    }

    public function testTheJsHooksDevnetFaucet(): void
    {
        $this->assertSame(
            XahauFaucet::DEVNET_HOST,
            $this->faucetFor('https://jshooks.xahau-test.net')->faucetHost()
        );
    }

    /**
     * There is no faucet on mainnet. Answering with a testnet one would hand
     * back a wallet that has no money on the network the caller is talking to.
     */
    public function testMainnetHasNoFaucet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no Xahau faucet');

        $this->faucetFor(Xahau::MAINNET)->faucetHost();
    }
}
