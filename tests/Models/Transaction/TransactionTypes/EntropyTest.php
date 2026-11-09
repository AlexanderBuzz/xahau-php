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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Entropy;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * A validator's share of the random number generator. A pseudo transaction:
 * the validators write it, so the model exists to read it back.
 */
final class EntropyTest extends TransactionTypeTestCase
{
    /** Ledger 13909898 of the Xahau testnet, as the node returned it. */
    private const TESTNET_BLOB = '12005924000000002600D43F8A50590F16D8B234473F74BFBE289194127F8B85FCDC'
        . '1824746CC9DF38416FE4E4D2BF505A466B8A7BE0573881F5D59611829C9D9381AE83E57D1C1BAF30235FC94CF929'
        . '1E6840000000000000007121ED49F82B2FFD537F224A1E0A10DEEFC3C25CE3882979E6B327C9F18603D21F0A2273'
        . '008100';

    protected static function modelClass(): string
    {
        return Entropy::class;
    }

    public static function transactions(): array
    {
        $commitment = [
            'TransactionType' => 'Entropy',
            'LedgerSequence' => 13909898,
            'PublicKey' => 'ED49F82B2FFD537F224A1E0A10DEEFC3C25CE3882979E6B327C9F18603D21F0A22',
            'NextRandomDigest' => '0F16D8B234473F74BFBE289194127F8B85FCDC1824746CC9DF38416FE4E4D2BF',
        ];

        return [
            'the first commitment, nothing to reveal yet' => [self::common($commitment)],
            'a reveal and the next commitment' => [self::common($commitment + [
                'RandomData' => '466B8A7BE0573881F5D59611829C9D9381AE83E57D1C1BAF30235FC94CF9291E',
            ])],
        ];
    }

    public function testItReadsARealOneOffTheTestnet(): void
    {
        $codec = Xahau::codec();
        $tx = $codec->decode(self::TESTNET_BLOB);

        $this->assertSame('Entropy', $tx['TransactionType']);
        $this->assertSame(13909898, $tx['LedgerSequence']);
        $this->assertSame('466B8A7BE0573881F5D59611829C9D9381AE83E57D1C1BAF30235FC94CF9291E', $tx['RandomData']);
        // The Account of a pseudo transaction is empty on the wire, not zero.
        $this->assertSame('', $tx['Account']);
        $this->assertSame(self::TESTNET_BLOB, $codec->encode($tx));
    }
}
