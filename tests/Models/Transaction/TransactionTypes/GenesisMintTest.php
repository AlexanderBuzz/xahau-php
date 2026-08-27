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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\GenesisMint;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * A pseudo transaction emitted by the governance hook, never submitted by a
 * client. The model is here so that it can be read back out of a ledger.
 *
 * https://docs.xahau.network/features/network/governance
 */
final class GenesisMintTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return GenesisMint::class;
    }

    public static function transactions(): array
    {
        return [
            'minting to two accounts' => [self::common([
                'TransactionType' => 'GenesisMint',
                'GenesisMints' => [
                    ['GenesisMint' => [
                        'Destination' => self::DESTINATION,
                        'Amount' => '1000000',
                    ]],
                    ['GenesisMint' => [
                        'Destination' => self::THIRD_PARTY,
                        'GovernanceFlags' => '0101010101010101010101010101010101010101010101010101010101010101',
                    ]],
                ],
            ])],
        ];
    }
}
