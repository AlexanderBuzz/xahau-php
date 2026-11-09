<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypes;

use Exception;
use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\AccountSet;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet as XrplAccountSet;

/**
 * AccountSet with the two fields only Xahau has, which is the reason the type
 * is modelled here although the XRP Ledger shares it.
 */
final class AccountSetTest extends TransactionTypeTestCase
{
    private const BOOTLOADER_HEX = '3C21444F43545950452068746D6C3E3C68746D6C3E3C2F68746D6C3E';

    protected static function modelClass(): string
    {
        return AccountSet::class;
    }

    public static function transactions(): array
    {
        return [
            'publishing a bootloader' => [self::common([
                'TransactionType' => 'AccountSet',
                'AppLoader' => self::BOOTLOADER_HEX,
            ])],
            'more hook state' => [self::common([
                'TransactionType' => 'AccountSet',
                'HookStateScale' => 2,
            ])],
            'everything else, as on the XRP Ledger' => [self::common([
                'TransactionType' => 'AccountSet',
                'Domain' => '6578616D706C652E636F6D',
                'EmailHash' => '98B4375E1D753E5B91627516F6D70977',
                'MessageKey' => '03AB40A0490F9B7ED8DF29D246BF2D6269820A0EE7742ACDD457BEA7C7D0931EDB',
                'SetFlag' => 5,
                'ClearFlag' => 6,
                'TransferRate' => 1000000000,
                'TickSize' => 5,
                'WalletLocator' => 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB',
                'WalletSize' => 1,
                'NFTokenMinter' => self::DESTINATION,
            ])],
        ];
    }

    public function testTheSharedModelRefusesWhatOnlyXahauHas(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('AppLoader');

        new XrplAccountSet(['Account' => self::ACCOUNT, 'AppLoader' => self::BOOTLOADER_HEX]);
    }
}
