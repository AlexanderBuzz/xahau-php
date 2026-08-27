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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\URITokenBurn;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenburn
 */
final class URITokenBurnTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return URITokenBurn::class;
    }

    public static function transactions(): array
    {
        return [
            'burning a token' => [self::common([
                'TransactionType' => 'URITokenBurn',
                'URITokenID' => 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD',
            ])],
        ];
    }

    /**
     * Ordinal 46.
     */
    public function testTheXrplCodecReadsItAsXChainAddAccountCreateAttestation(): void
    {
        $this->assertTheXrplCodecReadsItAs('XChainAddAccountCreateAttestation');
    }
}
