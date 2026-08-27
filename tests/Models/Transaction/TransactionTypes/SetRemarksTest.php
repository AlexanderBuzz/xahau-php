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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\SetRemarks;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/features/transaction-types/setremarks
 */
final class SetRemarksTest extends TransactionTypeTestCase
{
    private const OBJECT_ID = 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    /** tfImmutable */
    private const REMARK_IMMUTABLE = 1;

    protected static function modelClass(): string
    {
        return SetRemarks::class;
    }

    public static function transactions(): array
    {
        return [
            'one remark' => [self::common([
                'TransactionType' => 'SetRemarks',
                'ObjectID' => self::OBJECT_ID,
                'Remarks' => [
                    ['Remark' => ['RemarkName' => '6E616D65', 'RemarkValue' => '76616C7565']],
                ],
            ])],
            // Remarks is an STArray of STObjects, so this is where the
            // definitions have to survive two levels down rather than one.
            'several remarks, one of them immutable' => [self::common([
                'TransactionType' => 'SetRemarks',
                'ObjectID' => self::OBJECT_ID,
                'Remarks' => [
                    ['Remark' => [
                        'RemarkName' => '6E616D65',
                        'RemarkValue' => '76616C7565',
                        'Flags' => self::REMARK_IMMUTABLE,
                    ]],
                    ['Remark' => ['RemarkName' => '6F74686572']],
                ],
            ])],
            // Deleting a remark is expressed by a name without a value.
            'deleting a remark' => [self::common([
                'TransactionType' => 'SetRemarks',
                'ObjectID' => self::OBJECT_ID,
                'Remarks' => [
                    ['Remark' => ['RemarkName' => '6E616D65']],
                ],
            ])],
        ];
    }
}
