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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\URITokenMint;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenmint
 */
final class URITokenMintTest extends TransactionTypeTestCase
{
    /** tfBurnable */
    private const TF_BURNABLE = 1;

    protected static function modelClass(): string
    {
        return URITokenMint::class;
    }

    public static function transactions(): array
    {
        return [
            'just a URI' => [self::common([
                'TransactionType' => 'URITokenMint',
                'URI' => '68747470733A2F2F6578616D706C652E636F6D',
            ])],
            'burnable, with a digest' => [self::common([
                'TransactionType' => 'URITokenMint',
                'URI' => '68747470733A2F2F6578616D706C652E636F6D',
                'Digest' => 'EFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEF',
                'Flags' => self::TF_BURNABLE,
            ])],
            'minted and offered in one transaction' => [self::common([
                'TransactionType' => 'URITokenMint',
                'URI' => '68747470733A2F2F6578616D706C652E636F6D',
                'Amount' => '1000000',
                'Destination' => self::DESTINATION,
            ])],
        ];
    }

    /**
     * The regression this package exists for: ordinal 45 is URITokenMint here
     * and XChainAddClaimAttestation on the XRP Ledger.
     */
    public function testTheXrplCodecReadsItAsXChainAddClaimAttestation(): void
    {
        $this->assertTheXrplCodecReadsItAs('XChainAddClaimAttestation');
    }
}
