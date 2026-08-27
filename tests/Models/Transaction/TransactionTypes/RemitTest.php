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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Remit;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * https://docs.xahau.network/features/transaction-types/remit
 */
final class RemitTest extends TransactionTypeTestCase
{
    private const URITOKEN_ID = 'CDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD';

    private const DIGEST = 'EFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEFEF';

    protected static function modelClass(): string
    {
        return Remit::class;
    }

    public static function transactions(): array
    {
        return [
            'plain' => [self::common([
                'TransactionType' => 'Remit',
                'Destination' => self::DESTINATION,
            ])],
            'with a blob and an invoice' => [self::common([
                'TransactionType' => 'Remit',
                'Destination' => self::DESTINATION,
                'DestinationTag' => 4711,
                'InvoiceID' => str_repeat('AB', 32),
                'Blob' => 'CAFE',
                'Inform' => self::THIRD_PARTY,
            ])],
            'sending amounts' => [self::common([
                'TransactionType' => 'Remit',
                'Destination' => self::DESTINATION,
                'Amounts' => [
                    ['AmountEntry' => ['Amount' => '1000000']],
                    ['AmountEntry' => ['Amount' => [
                        'currency' => 'USD',
                        'issuer' => self::THIRD_PARTY,
                        'value' => '10',
                    ]]],
                ],
            ])],
            'sending URITokens' => [self::common([
                'TransactionType' => 'Remit',
                'Destination' => self::DESTINATION,
                'URITokenIDs' => [self::URITOKEN_ID],
            ])],
            'minting a URIToken' => [self::common([
                'TransactionType' => 'Remit',
                'Destination' => self::DESTINATION,
                'MintURIToken' => [
                    'URI' => '68747470733A2F2F6578616D706C652E636F6D',
                    'Digest' => self::DIGEST,
                    'Flags' => 1,
                ],
            ])],
        ];
    }

    /**
     * The case Remit exists for: mint a URIToken and send it together with
     * some amounts, in one transaction.
     *
     * It does not encode correctly today, and the defect is in xrpl_php's
     * codec rather than in this package. StObject::fromJson() appends the
     * ObjectEndMarker after its loop, guarded by the loop variable that
     * happens to be left over from the last field:
     *
     *     if (isset($fieldInstance) && $fieldInstance->getType() === self::ST_OBJECT) {
     *         $binarySerializer->put(self::OBJECT_END_MARKER_HEX);
     *     }
     *
     * A nested object is therefore terminated only when it is the last field
     * of its parent. Fields are written in canonical order - by type ordinal,
     * then field number - so a nested STObject (type 14) is last only when the
     * parent has no field of a higher-numbered type. The moment one does, the
     * marker is dropped and the decoder reads the following field as part of
     * the nested object. For Remit that is Amounts (STArray, 15) and
     * URITokenIDs (Vector256, 19); MintURIToken next to a Blob (7) is fine,
     * because the Blob sorts ahead of it.
     *
     * The test is written now and marked incomplete rather than left out, so
     * that it turns green by itself once xrpl_php terminates nested objects
     * where they end. Do not work around it here: per the ground rules, a
     * codec defect is fixed in xrpl_php, not patched around in this package.
     */
    public function testItMintsAUriTokenAndSendsAmountsAtOnce(): void
    {
        $tx = self::common([
            'TransactionType' => 'Remit',
            'Destination' => self::DESTINATION,
            'MintURIToken' => ['URI' => '68747470', 'Digest' => self::DIGEST],
            'Amounts' => [['AmountEntry' => ['Amount' => '1000000']]],
        ]);

        $codec = Xahau::codec();
        $decoded = $codec->decode($codec->encode($tx));

        if ($decoded != $tx) {
            $this->markTestIncomplete(
                'xrpl_php StObject::fromJson() only writes the ObjectEndMarker when the nested object is the '
                . 'last field of its parent, so MintURIToken swallows Amounts. See the comment above.'
            );
        }

        $this->assertEquals($tx, $decoded);
    }
}
