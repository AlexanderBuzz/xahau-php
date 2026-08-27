<?php declare(strict_types=1);

namespace Hardcastle\Xahau_PHP\Test;

use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Remit;
use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\SetRemarks;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * Does hardcastle/xrpl_php actually carry a foreign network from the outside?
 *
 * This is the acceptance test for the seam added in xrpl_php 2.1.0. Everything
 * here goes through its public API - nothing is copied, subclassed around or
 * reached into.
 */
final class SeamTest extends TestCase
{
    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const OTHER = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private static function ordinalOf(string $hex): int
    {
        return hexdec(substr($hex, 2, 4));
    }

    /**
     * The ordinals of the two networks differ for most shared types. If this
     * fails, the bundled definitions are not being used at all.
     */
    public function testSharedTypesUseXahauOrdinals(): void
    {
        $tx = [
            'TransactionType' => 'MPTokenIssuanceCreate',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
            'AssetScale' => 2,
        ];

        $this->assertEquals(63, self::ordinalOf(Xahau::codec()->encode($tx)));
        $this->assertEquals(54, self::ordinalOf((new BinaryCodec())->encode($tx)));
    }

    /**
     * Payment is one of the 36 types both networks agree on.
     */
    public function testSharedOrdinalsStayIdentical(): void
    {
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::OTHER,
            'Amount' => '1000',
            'Fee' => '10',
            'Sequence' => 1,
        ];

        $this->assertEquals(
            (new BinaryCodec())->encode($tx),
            Xahau::codec()->encode($tx)
        );
    }

    /**
     * A model class defined in this package on top of the base class from
     * xrpl_php.
     */
    public function testRemitModelRoundtrips(): void
    {
        $tx = [
            'TransactionType' => 'Remit',
            'Account' => self::ACCOUNT,
            'Destination' => self::OTHER,
            'Fee' => '10',
            'Sequence' => 1,
            'NetworkID' => Xahau::MAINNET_NETWORK_ID,
            'Blob' => 'CAFE',
        ];

        $model = new Remit($tx);
        $codec = Xahau::codec();
        $decoded = $codec->decode($codec->encode($model->toArray()));

        $this->assertEquals('Remit', $decoded['TransactionType']);
        $this->assertEquals('CAFE', $decoded['Blob']);
        $this->assertEquals(Xahau::MAINNET_NETWORK_ID, $decoded['NetworkID']);
    }

    /**
     * Remarks are an STArray of STObjects, so the definitions have to survive
     * into the nested structure.
     */
    public function testSetRemarksWithNestedArray(): void
    {
        $tx = [
            'TransactionType' => 'SetRemarks',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
            'ObjectID' => str_repeat('AB', 32),
            'Remarks' => [
                ['Remark' => ['RemarkName' => '6E616D65', 'RemarkValue' => '76616C7565']],
            ],
        ];

        $model = new SetRemarks($tx);
        $codec = Xahau::codec();
        $decoded = $codec->decode($codec->encode($model->toArray()));

        $this->assertEquals('SetRemarks', $decoded['TransactionType']);
        $this->assertEquals('6E616D65', $decoded['Remarks'][0]['Remark']['RemarkName']);
        $this->assertEquals('76616C7565', $decoded['Remarks'][0]['Remark']['RemarkValue']);
    }

    /**
     * The whole point: signing has to run against Xahau's definitions, hash
     * included.
     */
    public function testSigningAXahauOnlyTransaction(): void
    {
        $wallet = Xahau::wallet(self::SEED);

        $signed = $wallet->sign([
            'TransactionType' => 'Remit',
            'Account' => $wallet->getAddress(),
            'Destination' => self::OTHER,
            'Fee' => '10',
            'Sequence' => 1,
            'NetworkID' => Xahau::MAINNET_NETWORK_ID,
        ]);

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertEquals(64, strlen($signed['hash']));
        $this->assertEquals(
            'Remit',
            Xahau::codec()->decode($signed['tx_blob'])['TransactionType']
        );
    }

    /**
     * URIToken types share their ordinals with XChain types on the XRP Ledger.
     * Reading them back through the bundled XRPL definitions is what 2.0.0
     * could not get right - here each network reads its own.
     */
    public function testUriTokenDecodesAsItself(): void
    {
        $codec = Xahau::codec();
        $hex = $codec->encode([
            'TransactionType' => 'URITokenMint',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
            'URI' => '68747470',
        ]);

        $this->assertEquals('URITokenMint', $codec->decode($hex)['TransactionType']);
        $this->assertEquals(
            'XChainAddClaimAttestation',
            (new BinaryCodec())->decode($hex)['TransactionType']
        );
    }

    public function testDefinitionsDoNotLeakIntoTheDefaultInstance(): void
    {
        Xahau::codec();

        $tx = [
            'TransactionType' => 'MPTokenIssuanceCreate',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
        ];

        $this->assertEquals(54, self::ordinalOf((new BinaryCodec())->encode($tx)));
    }
}
