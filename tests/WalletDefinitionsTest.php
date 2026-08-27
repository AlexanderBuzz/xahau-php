<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test;

use Exception;
use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use PHPUnit\Framework\TestCase;

/**
 * A wallet signs through the binary codec, so it carries definitions too.
 *
 * This is the quietest way to get a Xahau application wrong. Wallet::generate()
 * builds a wallet on the XRP Ledger's definitions, and that keeps working for
 * as long as every field of every transaction happens to exist on both
 * networks - which is most of them. It fails at the first that does not, and
 * it will fail at all of them once xrpl_php 3.0.0 stops merging its Hooks
 * definitions into the defaults.
 *
 * HookName is the field that makes the difference visible today: Xahau gives
 * it field number 97, and the XRP Ledger has no such field at all.
 */
final class WalletDefinitionsTest extends TestCase
{
    private static function setHook(string $account): array
    {
        return [
            'TransactionType' => 'SetHook',
            'Account' => $account,
            'Fee' => '10',
            'Sequence' => 1,
            'NetworkID' => Xahau::TESTNET_NETWORK_ID,
            'Hooks' => [
                ['Hook' => [
                    'HookHash' => '1212121212121212121212121212121212121212121212121212121212121212',
                    'HookName' => '484F4F4B',
                ]],
            ],
        ];
    }

    public function testAWalletFromTheFacadeSignsXahauOnlyFields(): void
    {
        $wallet = Xahau::generateWallet();

        $signed = $wallet->sign(self::setHook($wallet->getAddress()));

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertSame(
            '484F4F4B',
            Xahau::codec()->decode($signed['tx_blob'])['Hooks'][0]['Hook']['HookName']
        );
    }

    /**
     * The other half of the statement: without definitions the same
     * transaction cannot be signed at all. If this ever stops throwing, the
     * XRP Ledger has grown a HookName and the test above proves less than it
     * looks like it does.
     */
    public function testAWalletWithoutDefinitionsCannotSignThem(): void
    {
        $wallet = Wallet::generate();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('HookName');

        $wallet->sign(self::setHook($wallet->getAddress()));
    }

    public function testAWalletFromASeedCarriesThemToo(): void
    {
        $wallet = Xahau::wallet('sEd7rnfWxwJmRditu2UpSsrZDRgtctn');

        $signed = $wallet->sign(self::setHook($wallet->getAddress()));

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
    }
}
