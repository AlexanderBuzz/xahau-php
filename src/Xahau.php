<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP;

use Exception;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Entry point for the Xahau network.
 *
 * Xahau and the XRP Ledger assign different ordinals to most of the
 * transaction types they share - MPTokenIssuanceCreate is 54 on the XRP Ledger
 * and 63 on Xahau - so the two cannot share one set of definitions. Everything
 * here does the same thing: build an xrpl_php object with Xahau's definitions
 * instead of the bundled XRP Ledger ones.
 */
final class Xahau
{
    public const MAINNET = 'https://xahau.network';

    public const TESTNET = 'https://xahau-test.net';

    public const MAINNET_NETWORK_ID = 21337;

    public const TESTNET_NETWORK_ID = 21338;

    private static ?Definitions $definitions = null;

    /**
     * The bundled Xahau definitions.
     *
     * Captured from a Xahau node via `server_definitions`; see
     * definitions/README.md for how to refresh them.
     *
     * @return Definitions
     * @throws Exception
     */
    public static function definitions(): Definitions
    {
        return self::$definitions ??= Definitions::fromFile(
            __DIR__ . '/../definitions/definitions.json'
        );
    }

    /**
     * Use definitions other than the bundled ones, e.g. fetched from a node at
     * runtime so that an amendment does not require a release here.
     *
     * @param Definitions $definitions
     * @return void
     */
    public static function useDefinitions(Definitions $definitions): void
    {
        self::$definitions = $definitions;
    }

    /**
     * @return BinaryCodec
     * @throws Exception
     */
    public static function codec(): BinaryCodec
    {
        return new BinaryCodec(self::definitions());
    }

    /**
     * @param string $seed
     * @return Wallet
     * @throws Exception
     */
    public static function wallet(string $seed): Wallet
    {
        return Wallet::fromSeed($seed, self::definitions());
    }

    /**
     * @param string $connectionUrl
     * @return JsonRpcClient
     * @throws Exception
     */
    public static function client(string $connectionUrl = self::MAINNET): JsonRpcClient
    {
        return new JsonRpcClient($connectionUrl, null, null, 3.0, self::definitions());
    }
}
