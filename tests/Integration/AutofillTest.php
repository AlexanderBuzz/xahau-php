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
 * What a transaction needs before it can be signed, against a real ledger.
 */
#[Group('integration')]
final class AutofillTest extends XahauIntegrationTestCase
{
    public function testEverythingATransactionNeedsIsFilledIn(): void
    {
        $filled = self::client()->autofill([
            'TransactionType' => 'Invoke',
            'Account' => self::primaryWallet()->getAddress(),
        ]);

        $ledgerIndex = self::client()->getLedgerIndex();

        $this->assertSame(Xahau::TESTNET_NETWORK_ID, $filled['NetworkID']);
        $this->assertGreaterThan(0, $filled['Sequence']);
        $this->assertGreaterThan(0, (int)$filled['Fee']);

        // LastLedgerSequence is placed ahead of the current ledger, not ahead
        // of the account sequence - the two are unrelated numbers here, and an
        // account created on Xahau starts at a sequence far above any ledger
        // index the network has reached.
        $this->assertGreaterThan($ledgerIndex, $filled['LastLedgerSequence']);
    }

    /**
     * The signature covers NetworkID, so a transaction signed for the wrong
     * network is simply a different transaction. Signing after autofill has to
     * verify.
     */
    public function testWhatComesOutOfAutofillCanBeSigned(): void
    {
        $wallet = self::primaryWallet();

        $signed = $wallet->sign(self::client()->autofill([
            'TransactionType' => 'Invoke',
            'Account' => $wallet->getAddress(),
        ]));

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertSame(
            Xahau::TESTNET_NETWORK_ID,
            Xahau::codec()->decode($signed['tx_blob'])['NetworkID']
        );
    }
}
