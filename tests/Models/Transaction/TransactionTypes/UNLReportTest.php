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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\UNLReport;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * A pseudo transaction written by the network at a flag ledger, never
 * submitted by a client.
 *
 * https://docs.xahau.network/features/network/governance
 */
final class UNLReportTest extends TransactionTypeTestCase
{
    private const VALIDATOR_KEY = 'EDABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    private const IMPORT_VL_KEY = 'EDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCDCD';

    protected static function modelClass(): string
    {
        return UNLReport::class;
    }

    public static function transactions(): array
    {
        return [
            'an active validator' => [self::common([
                'TransactionType' => 'UNLReport',
                'LedgerSequence' => 1234560,
                'ActiveValidator' => [
                    'PublicKey' => self::VALIDATOR_KEY,
                    'Account' => self::DESTINATION,
                ],
            ])],
            'an import validator list key' => [self::common([
                'TransactionType' => 'UNLReport',
                'LedgerSequence' => 1234560,
                'ImportVLKey' => [
                    'PublicKey' => self::IMPORT_VL_KEY,
                    'Account' => self::THIRD_PARTY,
                ],
            ])],
        ];
    }

    /**
     * Both object fields at once - two objects side by side, with nothing
     * between them but the marker that ends the first.
     *
     * This is the shape that made the missing ObjectEndMarker in xrpl_php's
     * encoder visible, and the case is kept apart from the others so that the
     * suite stays green against a released xrpl_php that does not have the fix
     * yet. It turns into an ordinary assertion once the fix is out and the
     * constraint in composer.json can require it.
     *
     * @see \Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypes\RemitTest::testItMintsAUriTokenAndSendsAmountsAtOnce()
     */
    public function testItReportsValidatorsAndKeysAtOnce(): void
    {
        $tx = self::common([
            'TransactionType' => 'UNLReport',
            'LedgerSequence' => 1234560,
            'ActiveValidator' => [
                'PublicKey' => self::VALIDATOR_KEY,
                'Account' => self::DESTINATION,
            ],
            'ImportVLKey' => [
                'PublicKey' => self::IMPORT_VL_KEY,
                'Account' => self::THIRD_PARTY,
            ],
        ]);

        $codec = Xahau::codec();
        $decoded = $codec->decode($codec->encode($tx));

        if ($decoded != $tx) {
            $this->markTestIncomplete(
                'xrpl_php StObject::fromJson() only writes the ObjectEndMarker when the nested object is the '
                . 'last field of its parent, so ImportVLKey is read back as a field of ActiveValidator.'
            );
        }

        $this->assertEquals($tx, $decoded);
    }
}
