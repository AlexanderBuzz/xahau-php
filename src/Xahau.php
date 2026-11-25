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
use Hardcastle\Xahau_PHP\Client\XahauClient;
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
     * A wallet for an existing account.
     *
     * @param string $seed
     * @return Wallet
     * @throws Exception
     */
    public static function wallet(string $seed): Wallet
    {
        return Wallet::fromSeed($seed, self::definitions());
    }

    /**
     * A wallet for a new account.
     *
     * Signing runs through the binary codec, so a wallet built without
     * definitions signs against the XRP Ledger's - which mostly works, right
     * up to the first field the two networks do not share. Wallet::generate()
     * on its own is therefore the wrong call in a Xahau application.
     *
     * @param string $algorithm
     * @return Wallet
     * @throws Exception
     */
    public static function generateWallet(string $algorithm = Wallet::DEFAULT_ALGORITHM): Wallet
    {
        return Wallet::generate($algorithm, self::definitions());
    }

    /**
     * Verify the signature of a Xahau transaction somebody else signed.
     *
     * Wallet::verifySignature() of xrpl_php on Xahau's definitions: it checks
     * TxnSignature against the transaction's SigningPubKey, or against
     * $publicKey, offline, and accepts only the canonical signatures a node
     * accepts. Without Xahau's definitions a blob with a Xahau-only field
     * cannot even be decoded. It proves who signed, not that the key may sign
     * for Account; whether SigningPubKey is the account's master or regular
     * key is ledger state. A store of transactions seen should key on Account
     * and Sequence (or TicketSequence), not on the hash.
     *
     * @param string|array $transaction a tx_blob or the decoded transaction
     * @param string|null $publicKey the key to check against, hex; defaults to SigningPubKey
     * @return bool true if the signature is valid and canonical
     * @throws Exception if the transaction carries no TxnSignature (a
     *   multi-signed one included) or, without $publicKey, no SigningPubKey
     */
    public static function verifySignature(string|array $transaction, ?string $publicKey = null): bool
    {
        return Wallet::verifySignature($transaction, $publicKey, self::definitions());
    }

    /**
     * A client for a Xahau node.
     *
     * XahauClient is a JsonRpcClient with the three collaborators replaced
     * that Xahau needs differently: it fills in NetworkID, asks the node what
     * a transaction costs instead of applying the XRP Ledger's fee formula,
     * and knows where the Xahau faucets are.
     *
     * @param string $connectionUrl
     * @param int|null $networkId Pin it, rather than have it read from the node
     * @return XahauClient
     * @throws Exception
     */
    public static function client(string $connectionUrl = self::MAINNET, ?int $networkId = null): XahauClient
    {
        return new XahauClient($connectionUrl, null, null, 3.0, self::definitions(), $networkId);
    }
}
