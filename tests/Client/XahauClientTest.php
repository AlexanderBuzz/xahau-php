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

use donatj\MockWebServer\MockWebServer;
use Exception;
use Hardcastle\Xahau_PHP\Client\XahauClient;
use Hardcastle\Xahau_PHP\Test\MockNode\CannedNode;
use Hardcastle\Xahau_PHP\Xahau;
use PHPUnit\Framework\TestCase;

/**
 * The client, against a node that answers from a script.
 *
 * What is worth asserting here is the two places where Xahau differs from the
 * XRP Ledger and where getting it wrong fails quietly: the fee has to come
 * from the node rather than from a formula, and NetworkID has to be in the
 * transaction before the node is asked what it costs.
 */
final class XahauClientTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const DESTINATION = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    /**
     * Deliberately unlike anything the XRP Ledger's formula would produce from
     * the base fee below, so that a test cannot pass by accident.
     */
    private const NODE_FEE = '4242';

    /** Matches self::ACCOUNT */
    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private static MockWebServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = new MockWebServer();
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function setUp(): void
    {
        self::$server->setDefaultResponse(new CannedNode([
            'server_info' => [
                'info' => [
                    'network_id' => Xahau::TESTNET_NETWORK_ID,
                    // The XRP Ledger formula would make 12 drops of this.
                    'validated_ledger' => ['base_fee_xrp' => 0.00001],
                    'load_factor' => 1,
                ],
            ],
            'fee' => [
                'drops' => [
                    'base_fee' => self::NODE_FEE,
                    'base_fee_no_hooks' => '10',
                    'open_ledger_fee' => '11',
                ],
            ],
            'account_info' => ['account_data' => ['Sequence' => 1432]],
            'ledger' => ['ledger_index' => 2908714],
            'submit' => ['engine_result' => 'tesSUCCESS'],
        ]));
    }

    private function client(?int $networkId = null): XahauClient
    {
        return new XahauClient(
            self::$server->getServerRoot(),
            null,
            null,
            3.0,
            Xahau::definitions(),
            $networkId
        );
    }

    private function payment(): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
            'Amount' => '1000000',
        ];
    }

    public function testTheFeeComesFromTheNodeAndNotFromTheXrplFormula(): void
    {
        $tx = $this->client()->autofill($this->payment());

        $this->assertSame(self::NODE_FEE, $tx['Fee']);
    }

    public function testAutofillPutsTheNetworkIdIn(): void
    {
        $tx = $this->client()->autofill($this->payment());

        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $tx['NetworkID']);
    }

    public function testAPinnedNetworkIdIsLeftAlone(): void
    {
        $tx = $this->payment();
        $tx['NetworkID'] = Xahau::MAINNET_NETWORK_ID;

        $filled = $this->client()->autofill($tx);

        $this->assertSame(Xahau::MAINNET_NETWORK_ID, $filled['NetworkID']);
    }

    /**
     * The whole reason NetworkID is filled in before the fee: the node prices
     * the transaction it is shown, and a NetworkID added afterwards would be
     * missing from it - as well as from what gets signed.
     */
    public function testTheNodeIsAskedAboutATransactionThatCarriesTheNetworkId(): void
    {
        $this->client()->autofill($this->payment());

        $asked = Xahau::codec()->decode($this->lastFeeBlob());

        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $asked['NetworkID']);
        $this->assertSame('Payment', $asked['TransactionType']);
    }

    /**
     * The form the fee command wants: unsigned, and with the fee zeroed.
     */
    public function testTheTransactionTheNodeIsShownIsUnsignedAndCostsNothing(): void
    {
        $this->client()->autofill($this->payment());

        $asked = Xahau::codec()->decode($this->lastFeeBlob());

        $this->assertSame('', $asked['SigningPubKey']);
        $this->assertSame('0', $asked['Fee']);
    }

    /**
     * Those two are set on a copy. A caller who signed beforehand, or who
     * pinned a fee, must not find either of them cleared.
     */
    public function testTheCallersTransactionIsNotTouched(): void
    {
        $tx = $this->payment();
        $tx['SigningPubKey'] = 'ED0000000000000000000000000000000000000000000000000000000000000000';

        $filled = $this->client()->autofill($tx);

        $this->assertSame($tx['SigningPubKey'], $filled['SigningPubKey']);
        $this->assertSame(self::NODE_FEE, $filled['Fee']);
    }

    public function testMultiSigningIsPricedPerSignature(): void
    {
        $tx = $this->client()->autofill($this->payment(), 2);

        // open_ledger_fee + base_fee_no_hooks x (1 + signers) = 11 + 10 x 3
        $this->assertSame('41', $tx['Fee']);
    }

    public function testTheNetworkIdIsReadFromTheNodeOnlyOnce(): void
    {
        $client = $this->client();

        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $client->getNetworkId());
        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $client->getNetworkId());
    }

    public function testAPinnedNetworkIdIsUsedWithoutAskingTheNode(): void
    {
        $client = $this->client(Xahau::MAINNET_NETWORK_ID);

        $this->assertSame(Xahau::MAINNET_NETWORK_ID, $client->getNetworkId());
    }

    /**
     * Replacing a collaborator has to reach the paths the client does not walk
     * itself. submit() autofills through the Submitter, which asks the client
     * for its autofiller rather than building one - so this is where an
     * override that only worked on the direct path would show up.
     *
     * The assertion is on the transaction that was actually submitted, signed:
     * it has to carry the fee the node named and the NetworkID, or it is
     * signed for the wrong network at the wrong price.
     */
    public function testSubmittingGoesThroughTheXahauAutofiller(): void
    {
        $wallet = Xahau::wallet(self::SEED);

        $this->client()->submit($this->payment(), true, false, $wallet);

        $submitted = Xahau::codec()->decode($this->lastSubmittedBlob());

        $this->assertSame(self::NODE_FEE, $submitted['Fee']);
        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $submitted['NetworkID']);
        $this->assertTrue($wallet->verifyTransaction($this->lastSubmittedBlob()));
    }

    public function testANodeWithoutAFeeIsAnError(): void
    {
        self::$server->setDefaultResponse(new CannedNode([
            'fee' => ['drops' => ['median_fee' => '5000']],
        ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('drops.base_fee');

        $this->client()->getFeeCalculator()->getFeeEstimateDrops('1200');
    }

    private function lastFeeBlob(): string
    {
        return $this->blobOfLast('fee', 'tx_blob');
    }

    /**
     * The tx_blob of the last submit the node was sent.
     */
    private function lastSubmittedBlob(): string
    {
        return $this->blobOfLast('submit', 'tx_blob');
    }

    /**
     * A parameter of the most recent request for a given method.
     *
     * Searched for rather than taken from the last request: autofill asks for
     * the ledger index after the fee, so neither is reliably the last one.
     */
    private function blobOfLast(string $method, string $parameter): string
    {
        for ($offset = -1; $offset >= -20; $offset--) {
            $request = self::$server->getRequestByOffset($offset);

            if (is_null($request)) {
                break;
            }

            $body = $request->getInput();

            if (CannedNode::methodOf($body) === $method) {
                $blob = CannedNode::paramsOf($body)[$parameter] ?? null;
                $this->assertIsString($blob, "the {$method} request carried no {$parameter}");

                return $blob;
            }
        }

        $this->fail("the node was never sent a {$method} request");
    }

    /**
     * The short names resolve here, not in xrpl_php, whose network table is
     * the XRP Ledger's and carries no Xahau entries from 3.0.0 on. A URL goes
     * through untouched. Nothing is sent: the client only reads the node when
     * asked for the NetworkID or a fee.
     */
    public function testShortNamesResolveToTheXahauHosts(): void
    {
        $this->assertSame(Xahau::TESTNET, (new XahauClient('xahau_testnet', networkId: 1))->getConnectionUrl());
        $this->assertSame(Xahau::TESTNET, (new XahauClient('testnet', networkId: 1))->getConnectionUrl());
        $this->assertSame(Xahau::MAINNET, (new XahauClient('xahau_mainnet', networkId: 1))->getConnectionUrl());
        $this->assertSame(Xahau::MAINNET, (new XahauClient('mainnet', networkId: 1))->getConnectionUrl());
        $this->assertSame(Xahau::MAINNET, (new XahauClient(networkId: 1))->getConnectionUrl());
        $this->assertSame('https://node.example', (new XahauClient('https://node.example', networkId: 1))->getConnectionUrl());
    }
}
