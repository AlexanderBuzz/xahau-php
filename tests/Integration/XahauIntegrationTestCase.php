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

use Exception;
use Hardcastle\Xahau_PHP\Client\XahauClient;
use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\Xahau_PHP\Models\ServerInfo\FeatureRequest;
use Hardcastle\Xahau_PHP\Models\ServerInfo\FeatureResponse;
use Hardcastle\XRPL_PHP\Models\BaseRequest;
use Hardcastle\XRPL_PHP\Models\BaseResponse;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use PHPUnit\Framework\TestCase;

/**
 * The shared ground for the tests that talk to a real Xahau node.
 *
 * These are excluded from CI, by suite and by group. They are slow, they
 * depend on a faucet, and they fail for reasons that have nothing to do with
 * this package - which is exactly why they are worth having separately: what
 * they check is that the definitions, the fee model and the transaction
 * formats agree with a running network, and nothing offline can tell us that.
 *
 * Two environment variables steer them:
 *
 *   XAHAU_TESTNET_URL   the node, default https://xahau-test.net
 *   XAHAU_TEST_SEED     a funded wallet, so the faucet is left alone
 *
 * Without a seed the faucet is asked once per run and the wallet is shared by
 * every test, because the public faucets allow one request per client every 45
 * seconds. A second account is created by a Remit from the first rather than
 * by asking again.
 */
abstract class XahauIntegrationTestCase extends TestCase
{
    /** How long a faucet request is retried before the run gives up. */
    private const FAUCET_ATTEMPTS = 8;

    private const FAUCET_RETRY_SECONDS = 25;

    /** What the secondary account is given, well above the account reserve. */
    private const SECONDARY_FUNDING_DROPS = '100000000';

    private static ?XahauClient $client = null;

    private static ?Wallet $primary = null;

    private static ?Wallet $secondary = null;

    /** @var array<string, bool>|null */
    private static ?array $amendments = null;

    protected static function client(): XahauClient
    {
        $url = getenv('XAHAU_TESTNET_URL');

        return self::$client ??= Xahau::client(
            is_string($url) && $url !== '' ? $url : Xahau::TESTNET,
            // Pinned, so that every test does not open with a server_info.
            // ConnectionTest checks that the node agrees with this number.
            Xahau::TESTNET_NETWORK_ID
        );
    }

    /**
     * A funded account, shared by the whole run.
     *
     * @throws Exception
     */
    protected static function primaryWallet(): Wallet
    {
        if (!is_null(self::$primary)) {
            return self::$primary;
        }

        $seed = getenv('XAHAU_TEST_SEED');
        if (is_string($seed) && $seed !== '') {
            return self::$primary = Xahau::wallet($seed);
        }

        return self::$primary = self::askTheFaucet();
    }

    /**
     * A second funded account, created by the first.
     *
     * Remit funds an account that does not exist yet, which is what it is for,
     * so the faucet does not have to be asked twice - it would refuse anyway.
     *
     * @throws Exception
     */
    protected static function secondaryWallet(): Wallet
    {
        if (!is_null(self::$secondary)) {
            return self::$secondary;
        }

        $wallet = Wallet::generate();

        self::client()->submitAndWait([
            'TransactionType' => 'Remit',
            'Account' => self::primaryWallet()->getAddress(),
            'Destination' => $wallet->getAddress(),
            'Amounts' => [['AmountEntry' => ['Amount' => self::SECONDARY_FUNDING_DROPS]]],
        ], true, false, self::primaryWallet());

        return self::$secondary = $wallet;
    }

    /**
     * Submit, wait for the outcome and insist it succeeded.
     *
     * @return array The transaction as the ledger has it, metadata included
     * @throws Exception
     */
    protected function submitSuccessfully(array $tx, Wallet $wallet): array
    {
        $result = self::client()->submitAndWait($tx, true, false, $wallet)->getResult();

        $this->assertSame(
            'tesSUCCESS',
            $result['meta']['TransactionResult'] ?? null,
            "{$tx['TransactionType']} did not succeed: "
            . ($result['meta']['TransactionResult'] ?? 'no result at all')
        );

        return $result;
    }

    /**
     * Skip unless the amendment is enabled on the network being talked to.
     *
     * Xahau's definitions carry the transaction types of amendments that are
     * not necessarily live on it, so the codec will happily encode something
     * the network rejects. Reading that rejection as a defect in this package
     * would be a waste of an afternoon.
     *
     * @throws Exception
     */
    protected function requireAmendment(string $name): void
    {
        if (!(self::amendments()[$name] ?? false)) {
            $this->markTestSkipped("The amendment {$name} is not enabled on this network.");
        }
    }

    /**
     * @return array<string, bool> Amendment name => enabled
     * @throws Exception
     */
    private static function amendments(): array
    {
        if (!is_null(self::$amendments)) {
            return self::$amendments;
        }

        $response = self::client()->syncRequest(new FeatureRequest());

        if ($response instanceof ErrorResponse) {
            throw new Exception("feature: {$response->getError()}");
        }

        /** @var FeatureResponse $response */
        return self::$amendments = $response->getAmendments();
    }

    /**
     * @return array The result object of a request that has to succeed
     * @throws Exception
     */
    protected static function request(BaseRequest $request): array
    {
        $response = self::client()->syncRequest($request);

        if ($response instanceof ErrorResponse) {
            throw new Exception($request::class . ': ' . $response->getError());
        }

        if (!$response instanceof BaseResponse) {
            throw new Exception($request::class . ': the node did not answer with a JSON-RPC result');
        }

        return $response->getResult();
    }

    /**
     * The public faucets allow one request per client every 45 seconds, and
     * report the refusal in a body xrpl_php reads as a missing address - so a
     * cooldown and a real outage look the same from here, and both are worth
     * waiting out once.
     *
     * @throws Exception
     */
    private static function askTheFaucet(): Wallet
    {
        for ($attempt = 1; $attempt <= self::FAUCET_ATTEMPTS; $attempt++) {
            try {
                return self::client()->getFaucet()->fundWallet()['wallet'];
            } catch (Exception $exception) {
                if ($attempt === self::FAUCET_ATTEMPTS) {
                    throw new Exception(
                        'The faucet did not hand out a wallet in ' . self::FAUCET_ATTEMPTS . ' attempts. '
                        . 'Set XAHAU_TEST_SEED to a funded account to leave it out of the run.',
                        0,
                        $exception
                    );
                }

                sleep(self::FAUCET_RETRY_SECONDS);
            }
        }

        throw new Exception('The faucet was never asked; FAUCET_ATTEMPTS is not a positive number.');
    }
}
