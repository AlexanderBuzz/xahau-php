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

use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Import;
use Hardcastle\Xahau_PHP\Test\Models\Transaction\TransactionTypeTestCase;

/**
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/import
 */
final class ImportTest extends TransactionTypeTestCase
{
    protected static function modelClass(): string
    {
        return Import::class;
    }

    public static function transactions(): array
    {
        return [
            'a bare proof' => [self::common([
                'TransactionType' => 'Import',
                'Blob' => 'CAFEBABE',
            ])],
            'addressed to an issuer' => [self::common([
                'TransactionType' => 'Import',
                'Blob' => 'CAFEBABE',
                'Issuer' => self::THIRD_PARTY,
            ])],
        ];
    }
}
