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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\SetHook;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/sethook
 */
final class SetHookTest extends TransactionTypeTestCase
{
    private const NAMESPACE_ID = 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    private const HOOK_HASH = '1212121212121212121212121212121212121212121212121212121212121212';

    /** HookOn is a 256 bit mask over the transaction types, not an amount. */
    private const HOOK_ON_ALL = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF';

    /** hsfOverride */
    private const HSF_OVERRIDE = 1;

    protected static function modelClass(): string
    {
        return SetHook::class;
    }

    public static function transactions(): array
    {
        return [
            'installing a hook' => [self::common([
                'TransactionType' => 'SetHook',
                'Hooks' => [
                    ['Hook' => [
                        'CreateCode' => '0061736D01000000',
                        'HookApiVersion' => 0,
                        'HookNamespace' => self::NAMESPACE_ID,
                        'HookOn' => self::HOOK_ON_ALL,
                        'Flags' => self::HSF_OVERRIDE,
                    ]],
                ],
            ])],
            'a hook with parameters and grants' => [self::common([
                'TransactionType' => 'SetHook',
                'Hooks' => [
                    ['Hook' => [
                        'CreateCode' => '0061736D01000000',
                        'HookApiVersion' => 0,
                        'HookNamespace' => self::NAMESPACE_ID,
                        'HookOn' => self::HOOK_ON_ALL,
                        'HookName' => 'ABCDEF01',
                        'HookParameters' => [
                            ['HookParameter' => [
                                'HookParameterName' => 'CAFE',
                                'HookParameterValue' => 'BEEF',
                            ]],
                        ],
                        'HookGrants' => [
                            ['HookGrant' => [
                                'HookHash' => self::HOOK_HASH,
                                'Authorize' => self::THIRD_PARTY,
                            ]],
                        ],
                    ]],
                ],
            ])],
            'referencing a hook already on the ledger' => [self::common([
                'TransactionType' => 'SetHook',
                'Hooks' => [
                    // An empty Hook object leaves that position untouched, which is
                    // how a hook in a later slot is addressed on its own.
                    ['Hook' => []],
                    ['Hook' => ['HookHash' => self::HOOK_HASH]],
                ],
            ])],
        ];
    }
}
