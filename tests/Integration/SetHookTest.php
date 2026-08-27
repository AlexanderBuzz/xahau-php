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

use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Installing a hook and taking it off again.
 *
 * SetHook is the deepest structure in the package - an array of objects, each
 * with arrays of objects inside it - and the most expensive transaction Xahau
 * has, because a hook is charged by the byte. Both of those make it the test
 * that says the most about whether this package has the model right.
 *
 * The wasm is the accept hook from xahau.js' own integration suite: 141 bytes
 * that let every transaction through. Nothing here depends on what it does,
 * only that the network takes it.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/sethook
 */
#[Group('integration')]
final class SetHookTest extends XahauIntegrationTestCase
{
    private const ACCEPT_HOOK = '0061736D0100000001130360027F7F017F60037F7F7E017E60017F017E02170203656E76025F6700'
        . '0003656E760661636365707400010302010205030100020621057F01418088040B7F004180080B7F004180080B7F0041808804'
        . '0B7F004180080B07080104686F6F6B00020A9D80000199800000410141011080808080001A4100410042001081808080000B';

    /** hsfOverride, which is how a hook slot is emptied. */
    private const HSF_OVERRIDE = 1;

    public function testAHookIsInstalledAndRemovedAgain(): void
    {
        $this->requireAmendment('Hooks');

        $account = self::primaryWallet();

        $result = $this->submitSuccessfully([
            'TransactionType' => 'SetHook',
            'Account' => $account->getAddress(),
            'Hooks' => [
                ['Hook' => [
                    'CreateCode' => self::ACCEPT_HOOK,
                    'HookApiVersion' => 0,
                    'HookOn' => str_repeat('00', 32),
                    'HookNamespace' => str_repeat('00', 32),
                    // A Xahau-only field, and the one that showed the faucet
                    // was handing back wallets built on the XRP Ledger's
                    // definitions - they cannot sign it.
                    'HookName' => '484F4F4B',
                    'HookParameters' => [
                        ['HookParameter' => [
                            'HookParameterName' => 'DEADBEEF',
                            'HookParameterValue' => 'DEADBEEF',
                        ]],
                    ],
                ]],
            ],
        ], $account);

        $this->assertCount(1, $this->hooksOf($account->getAddress()));

        // A hook is charged per byte of web assembly, which puts this three
        // orders of magnitude above an ordinary transaction. No formula over
        // the network's load factor produces that; only the node does.
        $this->assertGreaterThan(10000, (int)$result['Fee']);

        $this->submitSuccessfully([
            'TransactionType' => 'SetHook',
            'Account' => $account->getAddress(),
            'Hooks' => [['Hook' => ['CreateCode' => '', 'Flags' => self::HSF_OVERRIDE]]],
        ], $account);

        $this->assertSame([], $this->hooksOf($account->getAddress()));
    }

    /**
     * The hooks installed on an account, as the ledger has them.
     */
    private function hooksOf(string $account): array
    {
        $objects = self::request(new AccountObjectsRequest(account: $account))['account_objects'] ?? [];

        foreach ($objects as $object) {
            if (($object['LedgerEntryType'] ?? null) === 'Hook') {
                return $object['Hooks'] ?? [];
            }
        }

        return [];
    }
}
