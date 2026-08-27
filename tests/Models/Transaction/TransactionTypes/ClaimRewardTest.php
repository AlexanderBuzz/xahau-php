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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\ClaimReward;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * https://docs.xahau.network/features/network/balance-rewards
 */
final class ClaimRewardTest extends TransactionTypeTestCase
{
    /** tfOptOut */
    private const TF_OPT_OUT = 1;

    protected static function modelClass(): string
    {
        return ClaimReward::class;
    }

    public static function transactions(): array
    {
        return [
            'enrolling' => [self::common([
                'TransactionType' => 'ClaimReward',
                'Issuer' => self::THIRD_PARTY,
            ])],
            'opting out' => [self::common([
                'TransactionType' => 'ClaimReward',
                'Issuer' => self::THIRD_PARTY,
                'Flags' => self::TF_OPT_OUT,
            ])],
            'claiming in an issued currency' => [self::common([
                'TransactionType' => 'ClaimReward',
                'Issuer' => self::THIRD_PARTY,
                'ClaimCurrency' => [
                    'currency' => 'USD',
                    'issuer' => self::THIRD_PARTY,
                ],
            ])],
        ];
    }

    /**
     * ClaimCurrency naming the native currency does not round trip, and the
     * reason is in xrpl_php rather than here: Issue and Currency both take the
     * string 'XRP' as the marker for the native currency, in fromJson as well
     * as in fromParser. Xahau's native code is XAH, which those two therefore
     * encode as an ordinary three letter currency - so the parser goes looking
     * for the issuer a native issue does not carry, and runs off the end of the
     * buffer.
     *
     * Unlike the definitions this cannot be supplied from outside: Issue and
     * Currency receive a JSON string and nothing else, so the seam added in
     * xrpl_php 2.1.0 does not reach them. The bundled definitions do carry the
     * answer - native_currency_code is XAH - but no leaf type can read it.
     *
     * Written now and marked incomplete, so that it turns green by itself once
     * the codec learns the network's native currency code.
     */
    public function testClaimingInTheNativeCurrency(): void
    {
        $tx = self::common([
            'TransactionType' => 'ClaimReward',
            'Issuer' => self::THIRD_PARTY,
            'ClaimCurrency' => ['currency' => 'XAH'],
        ]);

        $codec = Xahau::codec();

        try {
            $decoded = $codec->decode($codec->encode($tx));
        } catch (\Exception $exception) {
            $this->markTestIncomplete(
                'xrpl_php Issue and Currency hard-code XRP as the native currency code, so a native Xahau '
                . 'issue cannot be encoded: ' . $exception->getMessage()
            );
        }

        $this->assertEquals($tx, $decoded);
    }
}
