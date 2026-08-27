<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Models\Transaction;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;
use Hardcastle\Xahau_PHP\Xahau;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The definition of done for one transaction type, as a base class.
 *
 * A subclass names its model and supplies transactions; it inherits the three
 * checks every type owes: the model accepts each field, the transaction
 * survives the binary round trip through Xahau's definitions, and it signs and
 * hashes against them. Writing those three by hand per type invites leaving
 * one out, and the interesting types - the ones with nested arrays - are
 * exactly where that would matter.
 *
 * Where a type shares its ordinal with a different type on the XRP Ledger,
 * assertTheXrplCodecReadsItAs() adds the fourth check: that the confusion the
 * two networks are capable of actually happens, and in the direction expected.
 * That is the regression this whole package exists to prevent, so it is worth
 * asserting rather than assuming.
 */
abstract class TransactionTypeTestCase extends TestCase
{
    protected const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    protected const DESTINATION = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    protected const THIRD_PARTY = 'rLNaPoKeeBjZe2qs6x52yVPZpZ8td4dc6w';

    /** Matches self::ACCOUNT */
    protected const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    /**
     * The model under test.
     *
     * @return class-string<BaseTransaction>
     */
    abstract protected static function modelClass(): string;

    /**
     * The transactions to run the checks against. Cover the field list as far
     * as it goes: a minimal transaction and one that sets everything optional.
     *
     * @return array<string, array{0: array}>
     */
    abstract public static function transactions(): array;

    /**
     * Fill in the fields every transaction carries, so that a case in
     * transactions() shows only what is specific to its type.
     */
    protected static function common(array $fields): array
    {
        return array_merge([
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
            'NetworkID' => Xahau::MAINNET_NETWORK_ID,
        ], $fields);
    }

    #[DataProvider('transactions')]
    public function testTheModelAcceptsTheTransaction(array $tx): void
    {
        // Through reflection rather than `new $class(...)`: the constructor
        // lives on the shared base class in xrpl_php, and Psalm cannot know
        // that no model here narrows it.
        $model = (new ReflectionClass(static::modelClass()))->newInstance($tx);

        // fromArray() throws on a field the model does not declare, so this
        // also asserts that nothing in the fixture is missing from the class.
        $this->assertEquals($tx, $model->toArray());
    }

    #[DataProvider('transactions')]
    public function testItSurvivesTheBinaryRoundTrip(array $tx): void
    {
        $codec = Xahau::codec();

        $this->assertEquals($tx, $codec->decode($codec->encode($tx)));
    }

    /**
     * Encoding is only half of it: the hash a transaction is submitted under
     * is computed over the encoded form, so a wrong ordinal produces a valid
     * signature over the wrong transaction.
     */
    #[DataProvider('transactions')]
    public function testItSignsAgainstXahausDefinitions(array $tx): void
    {
        $wallet = Xahau::wallet(self::SEED);
        $tx['Account'] = $wallet->getAddress();

        $signed = $wallet->sign($tx);

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertSame(64, strlen($signed['hash']));
        $this->assertSame(
            $tx['TransactionType'],
            Xahau::codec()->decode($signed['tx_blob'])['TransactionType']
        );
    }

    /**
     * Assert that the XRP Ledger's own definitions read a transaction of this
     * type as a different type, and name which one.
     *
     * Only meaningful for the types whose ordinal is taken by something else on
     * the XRP Ledger - URITokenMint sits on the ordinal that network gives to
     * XChainAddClaimAttestation. Encoding is unambiguous only relative to a set
     * of definitions, and this is what proves the bundled set is the one in use.
     *
     * The transaction is built here, out of common fields only. A type specific
     * field would have to exist in both networks' definitions for the XRPL
     * codec to get as far as reading it, and the Xahau fields only exist there
     * today because xrpl_php still merges its Hooks definitions on top of the
     * XRP Ledger's - which 3.0.0 removes.
     */
    protected function assertTheXrplCodecReadsItAs(string $xrplType): void
    {
        $type = (new ReflectionClass(static::modelClass()))->getShortName();

        $hex = Xahau::codec()->encode([
            'TransactionType' => $type,
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
        ]);

        $this->assertSame($type, Xahau::codec()->decode($hex)['TransactionType']);
        $this->assertSame($xrplType, (new BinaryCodec())->decode($hex)['TransactionType']);
    }
}
