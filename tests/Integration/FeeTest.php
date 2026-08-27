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
use PHPUnit\Framework\Attributes\Group;

/**
 * Xahau prices the transaction, not the network minute.
 *
 * Nothing here is submitted: autofilling is enough, and what it asserts cannot
 * be asserted offline. A mocked node returns whatever the test told it to, so
 * it can prove the wiring but not the model. Only a real node can show that
 * two transactions of the same type cost different amounts.
 */
#[Group('integration')]
final class FeeTest extends XahauIntegrationTestCase
{
    /**
     * The one that would fail on the XRP Ledger's model.
     *
     * base fee x load factor x cushion does not look at the transaction, so it
     * would put the same number on both of these. The node does look, and the
     * blob is the only thing that differs.
     */
    public function testABiggerTransactionCostsMore(): void
    {
        $account = self::primaryWallet()->getAddress();

        $bare = self::client()->autofill([
            'TransactionType' => 'Invoke',
            'Account' => $account,
        ]);

        $loaded = self::client()->autofill([
            'TransactionType' => 'Invoke',
            'Account' => $account,
            'Blob' => str_repeat('AB', 200),
        ]);

        $this->assertGreaterThan(
            (int)$bare['Fee'],
            (int)$loaded['Fee'],
            'Both transactions were priced the same, so the fee is not coming from the node.'
        );
    }

    /**
     * The fee that ends up on the transaction is the one the node named for
     * exactly that transaction - not for some other form of it.
     */
    public function testTheFeeIsWhatTheNodeSaidAboutThisVeryTransaction(): void
    {
        $filled = self::client()->autofill([
            'TransactionType' => 'Invoke',
            'Account' => self::primaryWallet()->getAddress(),
            'Blob' => str_repeat('CD', 64),
        ]);

        // The form the fee command is asked about: unsigned, fee zeroed.
        $asked = $filled;
        $asked['SigningPubKey'] = '';
        $asked['Fee'] = '0';

        $this->assertSame(
            $filled['Fee'],
            self::client()->getFeeCalculator()->getFeeEstimateDrops(Xahau::codec()->encode($asked))
        );
    }

    /**
     * getFeeXrp() is inherited and still answers the network's base fee. It is
     * a different question, and the names have to stay apart - one is XRP, the
     * other drops.
     */
    public function testTheInheritedNetworkFeeIsStillThere(): void
    {
        $this->assertGreaterThan(0.0, (float)self::client()->getFeeCalculator()->getFeeXrp());
    }
}
