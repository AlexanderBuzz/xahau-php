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

use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;
use PHPUnit\Framework\Attributes\Group;

/**
 * A URIToken from minting to burning, and a remark on it along the way.
 *
 * One test rather than five, because each step needs the object the previous
 * one produced. Splitting them would only turn a dependency into an ordering
 * assumption.
 *
 * These five types are also the ones whose ordinals the XRP Ledger gives to
 * its XChain types, so a run that gets to the end is the strongest statement
 * this package can make: the network read every one of them as the type that
 * was meant.
 */
#[Group('integration')]
final class UriTokenLifecycleTest extends XahauIntegrationTestCase
{
    private const PRICE_DROPS = '1000000';

    public function testMintOfferBuyRemarkBurn(): void
    {
        $this->requireAmendment('URIToken');

        $issuer = self::primaryWallet();
        $buyer = self::secondaryWallet();

        // Unique per run, so that a repeat does not collide with the token a
        // previous run minted and left behind.
        $uri = strtoupper(bin2hex('https://example.com/' . bin2hex(random_bytes(4))));

        $this->submitSuccessfully([
            'TransactionType' => 'URITokenMint',
            'Account' => $issuer->getAddress(),
            'URI' => $uri,
            'Flags' => 1, // tfBurnable
        ], $issuer);

        $tokenId = $this->uriTokenIdOf($issuer->getAddress(), $uri);
        $this->assertNotNull($tokenId, 'the minted URIToken is not among the issuer objects');

        $this->submitSuccessfully([
            'TransactionType' => 'URITokenCreateSellOffer',
            'Account' => $issuer->getAddress(),
            'URITokenID' => $tokenId,
            'Amount' => self::PRICE_DROPS,
            'Destination' => $buyer->getAddress(),
        ], $issuer);

        $this->submitSuccessfully([
            'TransactionType' => 'URITokenBuy',
            'Account' => $buyer->getAddress(),
            'URITokenID' => $tokenId,
            'Amount' => self::PRICE_DROPS,
        ], $buyer);

        $this->assertNull(
            $this->uriTokenIdOf($issuer->getAddress(), $uri),
            'the issuer still owns the token after it was bought'
        );
        $this->assertSame($tokenId, $this->uriTokenIdOf($buyer->getAddress(), $uri));

        // Remarks are the issuer's to set, not the owner's: the buyer holds
        // the token now and still gets tecNO_PERMISSION for this.
        $this->submitSuccessfully([
            'TransactionType' => 'SetRemarks',
            'Account' => $issuer->getAddress(),
            'ObjectID' => $tokenId,
            'Remarks' => [
                ['Remark' => [
                    'RemarkName' => strtoupper(bin2hex('note')),
                    'RemarkValue' => strtoupper(bin2hex('sold')),
                ]],
            ],
        ], $issuer);

        $this->assertSame(
            [['Remark' => [
                'RemarkName' => strtoupper(bin2hex('note')),
                'RemarkValue' => strtoupper(bin2hex('sold')),
            ]]],
            $this->uriTokenOf($buyer->getAddress(), $uri)['Remarks'] ?? null
        );

        $this->submitSuccessfully([
            'TransactionType' => 'URITokenBurn',
            'Account' => $buyer->getAddress(),
            'URITokenID' => $tokenId,
        ], $buyer);

        $this->assertNull(
            $this->uriTokenIdOf($buyer->getAddress(), $uri),
            'the token survived being burned'
        );
    }

    /**
     * The URIToken object an account holds for a given URI, if any.
     */
    private function uriTokenOf(string $account, string $uri): ?array
    {
        $objects = self::request(new AccountObjectsRequest(account: $account))['account_objects'] ?? [];

        foreach ($objects as $object) {
            if (($object['LedgerEntryType'] ?? null) === 'URIToken' && ($object['URI'] ?? null) === $uri) {
                return $object;
            }
        }

        return null;
    }

    private function uriTokenIdOf(string $account, string $uri): ?string
    {
        return $this->uriTokenOf($account, $uri)['index'] ?? null;
    }
}
