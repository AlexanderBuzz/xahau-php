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

use PHPUnit\Framework\Attributes\Group;

/**
 * Invoke, the transaction that moves nothing and exists to set hooks off.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/invoke
 */
#[Group('integration')]
final class InvokeTest extends XahauIntegrationTestCase
{
    public function testItReachesAnotherAccountWithAPayload(): void
    {
        $this->requireAmendment('Hooks');

        $result = $this->submitSuccessfully([
            'TransactionType' => 'Invoke',
            'Account' => self::primaryWallet()->getAddress(),
            'Destination' => self::secondaryWallet()->getAddress(),
            'Blob' => 'CAFEBABE',
        ], self::primaryWallet());

        $this->assertSame('CAFEBABE', $result['Blob']);
        $this->assertSame(self::secondaryWallet()->getAddress(), $result['Destination']);
    }

    /**
     * HookParameters is a common field on Xahau, not part of any one
     * transaction type - which is why XahauTransaction carries it.
     */
    public function testItCarriesParametersForTheHooksItTriggers(): void
    {
        $this->requireAmendment('Hooks');

        $result = $this->submitSuccessfully([
            'TransactionType' => 'Invoke',
            'Account' => self::primaryWallet()->getAddress(),
            'HookParameters' => [
                ['HookParameter' => [
                    'HookParameterName' => 'CAFE',
                    'HookParameterValue' => 'BEEF',
                ]],
            ],
        ], self::primaryWallet());

        $this->assertSame(
            [['HookParameter' => ['HookParameterName' => 'CAFE', 'HookParameterValue' => 'BEEF']]],
            $result['HookParameters'] ?? null
        );
    }
}
