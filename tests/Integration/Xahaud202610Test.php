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

use Hardcastle\XRPL_PHP\Models\Ledger\LedgerRequest;
use Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\Entropy;
use Hardcastle\Xahau_PHP\Xahau;
use PHPUnit\Framework\Attributes\Group;

/**
 * The amendments of xahaud 2026.10.13 that a client meets: a bootloader on
 * AccountSet, the destination cancelling an escrow, and Entropy in every
 * ledger once RNG is enabled.
 *
 * https://github.com/Xahau/xahaud/releases/tag/2026.10.13-release%2B4119
 */
#[Group('integration')]
final class Xahaud202610Test extends XahauIntegrationTestCase
{
    private const SECONDS_TO_RIPPLE_EPOCH = 946684800;

    public function testAnAccountPublishesAndRemovesABootloader(): void
    {
        $this->requireAmendment('PWALoader');

        $wallet = self::primaryWallet();
        $html = '<!DOCTYPE html><html><body>xahau-php</body></html>';
        $hex = strtoupper(bin2hex($html));

        $published = $this->submitSuccessfully([
            'TransactionType' => 'AccountSet',
            'Account' => $wallet->getAddress(),
            'AppLoader' => $hex,
        ], $wallet);

        $this->assertSame($hex, $published['AppLoader']);
        // One drop per byte comes on top of the base fee, and the client has
        // to have asked the node for that rather than worked it out itself.
        $this->assertGreaterThan(strlen($html), (int)$published['Fee']);
        fwrite(STDERR, PHP_EOL . "AccountSet AppLoader: {$published['hash']}" . PHP_EOL);

        $removed = $this->submitSuccessfully([
            'TransactionType' => 'AccountSet',
            'Account' => $wallet->getAddress(),
            'AppLoader' => '',
        ], $wallet);
        fwrite(STDERR, "AccountSet AppLoader removed: {$removed['hash']}" . PHP_EOL);
    }

    public function testTheDestinationCancelsAnEscrow(): void
    {
        $this->requireAmendment('EscrowDestinationCancel');

        $owner = self::primaryWallet();
        $destination = self::secondaryWallet();

        $created = $this->submitSuccessfully([
            'TransactionType' => 'EscrowCreate',
            'Account' => $owner->getAddress(),
            'Destination' => $destination->getAddress(),
            'Amount' => '1000000',
            'FinishAfter' => time() - self::SECONDS_TO_RIPPLE_EPOCH + 86400,
        ], $owner);

        $cancelled = $this->submitSuccessfully([
            'TransactionType' => 'EscrowCancel',
            'Account' => $destination->getAddress(),
            'Owner' => $owner->getAddress(),
            'OfferSequence' => $created['Sequence'],
        ], $destination);

        fwrite(STDERR, PHP_EOL . "EscrowCreate: {$created['hash']}, cancelled by the destination: {$cancelled['hash']}" . PHP_EOL);
    }

    public function testALedgerCarriesEntropyTheModelCanRead(): void
    {
        $this->requireAmendment('RNG');

        $ledger = self::request(new LedgerRequest(
            ledgerIndex: 'validated',
            transactions: true,
            expand: true
        ))['ledger'];

        $entropy = array_values(array_filter(
            $ledger['transactions'],
            fn (array $tx): bool => $tx['TransactionType'] === 'Entropy'
        ));

        $this->assertNotEmpty($entropy, 'RNG is enabled, so every ledger should carry Entropy.');

        foreach ($entropy as $tx) {
            unset($tx['hash'], $tx['metaData']);
            $this->assertEquals($tx, (new Entropy($tx))->toArray());
        }
    }

    public function testALedgerDecodesFromItsBinaryForm(): void
    {
        $this->requireAmendment('RNG');

        $ledger = self::request(new LedgerRequest(
            ledgerIndex: 'validated',
            transactions: true,
            expand: true,
            binary: true
        ))['ledger'];

        $codec = Xahau::codec();
        $types = [];

        foreach ($ledger['transactions'] as $tx) {
            $decoded = $codec->decode($tx['tx_blob']);
            $types[] = $decoded['TransactionType'];

            $this->assertSame($tx['tx_blob'], $codec->encode($decoded));
            $this->assertArrayHasKey('AffectedNodes', $codec->decode($tx['meta']));
        }

        $this->assertContains('Entropy', $types);
    }
}
