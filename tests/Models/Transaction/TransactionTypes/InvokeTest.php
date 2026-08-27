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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Invoke;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/invoke
 */
final class InvokeTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return Invoke::class;
    }

    public static function transactions(): array
    {
        return [
            'triggering the hooks of the sender alone' => [self::common([
                'TransactionType' => 'Invoke',
            ])],
            'addressed at another account, with a payload' => [self::common([
                'TransactionType' => 'Invoke',
                'Destination' => self::DESTINATION,
                'DestinationTag' => 4711,
                'Blob' => 'CAFEBABE',
                'InvoiceID' => 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB',
            ])],
            'carrying parameters for the hooks it triggers' => [self::common([
                'TransactionType' => 'Invoke',
                'Destination' => self::DESTINATION,
                // HookParameters is a common field on Xahau rather than part of
                // Invoke: any transaction can pass values to the hooks it sets off.
                'HookParameters' => [
                    ['HookParameter' => [
                        'HookParameterName' => 'CAFE',
                        'HookParameterValue' => 'BEEF',
                    ]],
                ],
            ])],
        ];
    }
}
