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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\URITokenCancelSellOffer;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokencancelselloffer
 */
final class URITokenCancelSellOfferTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return URITokenCancelSellOffer::class;
    }

    public static function transactions(): array
    {
        return [
            'withdrawing the offer' => [self::common([
                'TransactionType' => 'URITokenCancelSellOffer',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
            ])],
        ];
    }

    /**
     * Ordinal 49.
     */
    public function testTheXrplCodecReadsItAsDIDSet(): void
    {
        $this->assertTheXrplCodecReadsItAs('DIDSet');
    }
}
