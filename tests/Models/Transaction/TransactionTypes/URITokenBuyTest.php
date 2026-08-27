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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\URITokenBuy;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenbuy
 */
final class URITokenBuyTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return URITokenBuy::class;
    }

    public static function transactions(): array
    {
        return [
            'paying in XAH' => [self::common([
                'TransactionType' => 'URITokenBuy',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
                'Amount' => '1000000',
            ])],
            'paying in an issued currency' => [self::common([
                'TransactionType' => 'URITokenBuy',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
                'Amount' => [
                    'currency' => 'USD',
                    'issuer' => self::THIRD_PARTY,
                    'value' => '10',
                ],
            ])],
        ];
    }

    /**
     * Ordinal 47.
     */
    public function testTheXrplCodecReadsItAsXChainModifyBridge(): void
    {
        $this->assertTheXrplCodecReadsItAs('XChainModifyBridge');
    }
}
