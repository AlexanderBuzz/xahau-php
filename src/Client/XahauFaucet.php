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

use Exception;
use Hardcastle\XRPL_PHP\Client\Faucet;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Funds a wallet from a Xahau test network faucet.
 *
 * xrpl_php works out the faucet from the connection URL, and its table has no
 * entry that matches: it looks for the substring 'testnet', and Xahau's
 * testnet is at xahau-test.net, which contains 'test.net' but not 'testnet'.
 * So the lookup throws rather than picking the wrong faucet - which is the
 * good outcome, and all that is needed here is to supply the right host.
 *
 * The faucets answer in the shape xrpl_php expects, so the funding itself,
 * including waiting for the balance to appear, is inherited.
 *
 * https://docs.xahau.network/features/faucet-and-explorer
 */
class XahauFaucet extends Faucet
{
    public const TESTNET_HOST = 'https://xahau-test.net';

    /** The JSHooks devnet, where hooks may be written in JavaScript. */
    public const DEVNET_HOST = 'https://jshooks.xahau-test.net';

    public const FAUCET_PATH = '/accounts';

    /**
     * The parent keeps the client to itself, so this holds its own reference.
     */
    protected readonly JsonRpcClient $client;

    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;

        parent::__construct($client);
    }

    /**
     * @param Wallet|null $wallet Funded if given and valid, otherwise a new one is generated
     * @param string|null $faucetHost Defaults to the faucet of the connected network
     * @param string|null $faucetPath
     * @param string|null $amount
     * @return array{wallet: Wallet, balance: float, fundWalletResponse: array}
     * @throws Exception
     */
    public function fundWallet(
        ?Wallet $wallet = null,
        ?string $faucetHost = null,
        ?string $faucetPath = null,
        ?string $amount = null
    ): array {
        // The wallet is generated here rather than left to the parent, which
        // would build it without definitions: a wallet signs through the
        // binary codec, so one made that way signs a Xahau transaction against
        // the XRP Ledger's definitions. That goes unnoticed for as long as
        // every field happens to exist on both networks, and stops at the
        // first that does not - HookName, for one.
        return parent::fundWallet(
            $wallet ?? Xahau::generateWallet(),
            $faucetHost ?? $this->faucetHost(),
            $faucetPath ?? self::FAUCET_PATH,
            $amount
        );
    }

    /**
     * The faucet belonging to the connected network.
     *
     * There is no faucet on mainnet, so a connection that is not recognisably
     * a test network is refused rather than pointed at one - funding a mainnet
     * address from a testnet faucet would silently produce a wallet with no
     * money on the network the caller is actually talking to.
     *
     * @return string
     * @throws Exception
     */
    public function faucetHost(): string
    {
        $connectionUrl = $this->client->getConnectionUrl();

        if (str_contains($connectionUrl, 'jshooks')) {
            return self::DEVNET_HOST;
        }

        if (str_contains($connectionUrl, 'xahau-test')) {
            return self::TESTNET_HOST;
        }

        throw new Exception(
            "There is no Xahau faucet for {$connectionUrl}. Pass a faucet host explicitly if you are running "
            . 'a network of your own.'
        );
    }
}
