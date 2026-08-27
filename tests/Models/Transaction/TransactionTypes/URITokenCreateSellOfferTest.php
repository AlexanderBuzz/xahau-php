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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\URITokenCreateSellOffer;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokencreateselloffer
 */
final class URITokenCreateSellOfferTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return URITokenCreateSellOffer::class;
    }

    public static function transactions(): array
    {
        return [
            'offered to anyone' => [self::common([
                'TransactionType' => 'URITokenCreateSellOffer',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
                'Amount' => '1000000',
            ])],
            'offered to one account' => [self::common([
                'TransactionType' => 'URITokenCreateSellOffer',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
                'Amount' => '1000000',
                'Destination' => self::DESTINATION,
            ])],
        ];
    }

    /**
     * Ordinal 48.
     */
    public function testTheXrplCodecReadsItAsXChainCreateBridge(): void
    {
        $this->assertTheXrplCodecReadsItAs('XChainCreateBridge');
    }
}
