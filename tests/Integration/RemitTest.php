<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Integration;

use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Remit, and the thing it can do that Payment cannot: deliver to an account
 * that does not exist yet, creating it on the way.
 *
 * https://docs.xahau.network/features/transaction-types/remit
 */
#[Group('integration')]
final class RemitTest extends XahauIntegrationTestCase
{
    public function testItCreatesAndFundsAnAccountThatDidNotExist(): void
    {
        $this->requireAmendment('Remit');

        // secondaryWallet() is created exactly this way, by a Remit from the
        // funded account, so asking for it here is the transaction under test.
        $created = self::secondaryWallet();

        $accountData = self::request(new AccountInfoRequest(account: $created->getAddress()))['account_data'];

        $this->assertSame($created->getAddress(), $accountData['Account']);
        $this->assertGreaterThan(0, (int)$accountData['Balance']);
    }

    public function testItCarriesABlobAndAnInvoiceId(): void
    {
        $this->requireAmendment('Remit');

        $result = $this->submitSuccessfully([
            'TransactionType' => 'Remit',
            'Account' => self::primaryWallet()->getAddress(),
            'Destination' => self::secondaryWallet()->getAddress(),
            'Amounts' => [['AmountEntry' => ['Amount' => '1000000']]],
            'Blob' => 'CAFEBABE',
            'InvoiceID' => 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB',
        ], self::primaryWallet());

        $this->assertSame('CAFEBABE', $result['Blob']);
        $this->assertSame(
            'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB',
            $result['InvoiceID']
        );
    }
}
