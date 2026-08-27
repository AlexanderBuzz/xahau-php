<?php declare(strict_types=1);
/**
 * A URIToken from minting to burning, with a remark along the way.
 *
 * URITokens are Xahau's answer to an NFT, and they are simpler than the XRP
 * Ledger's: one ledger object per token, carrying its own sell offer, so there
 * is never more than one offer outstanding and no offer objects to clean up.
 *
 * These five transaction types are also the reason this package exists. Their
 * ordinals - 45 to 49 - belong to the XChain types on the XRP Ledger, so a
 * codec on the wrong definitions reads a URITokenMint as an
 * XChainAddClaimAttestation and is confidently wrong about it.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/uritokenmint
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;

const TF_BURNABLE = 1;
const PRICE_DROPS = '1000000';   // 1 XAH

$client = xahauClient();
$issuer = fundedWallet($client);

/**
 * The URIToken an account holds for a given URI, if any.
 */
function tokenOf($client, string $account, string $uri): ?array
{
    $objects = $client->syncRequest(new AccountObjectsRequest(account: $account))
        ->getResult()['account_objects'] ?? [];

    foreach ($objects as $object) {
        if (($object['LedgerEntryType'] ?? null) === 'URIToken' && ($object['URI'] ?? null) === $uri) {
            return $object;
        }
    }

    return null;
}

heading('A buyer, brought into being by a Remit');

$buyer = Xahau::generateWallet();
submit($client, $issuer, [
    'TransactionType' => 'Remit',
    'Account' => $issuer->getAddress(),
    'Destination' => $buyer->getAddress(),
    'Amounts' => [['AmountEntry' => ['Amount' => '100000000']]],
]);
note("issuer       {$issuer->getAddress()}");
note("buyer        {$buyer->getAddress()}");

heading('Minting');

// Unique per run, so a second run does not collide with the token the first
// one left behind.
$uri = strtoupper(bin2hex('https://example.com/' . bin2hex(random_bytes(4))));

submit($client, $issuer, [
    'TransactionType' => 'URITokenMint',
    'Account' => $issuer->getAddress(),
    'URI' => $uri,
    'Flags' => TF_BURNABLE,
]);

$tokenId = tokenOf($client, $issuer->getAddress(), $uri)['index'] ?? null;
note("URITokenID   {$tokenId}");

heading('Offering it to the buyer, and the buyer taking it');

submit($client, $issuer, [
    'TransactionType' => 'URITokenCreateSellOffer',
    'Account' => $issuer->getAddress(),
    'URITokenID' => $tokenId,
    'Amount' => PRICE_DROPS,
    'Destination' => $buyer->getAddress(),
]);

submit($client, $buyer, [
    'TransactionType' => 'URITokenBuy',
    'Account' => $buyer->getAddress(),
    'URITokenID' => $tokenId,
    'Amount' => PRICE_DROPS,
]);

note('issuer still holds it   ' . (tokenOf($client, $issuer->getAddress(), $uri) ? 'yes' : 'no'));
note('buyer holds it          ' . (tokenOf($client, $buyer->getAddress(), $uri) ? 'yes' : 'no'));

heading('A remark on it');

/*
 * Remarks are the issuer's to set, not the owner's. The buyer holds the token
 * now and would get tecNO_PERMISSION for this exact transaction.
 */
submit($client, $issuer, [
    'TransactionType' => 'SetRemarks',
    'Account' => $issuer->getAddress(),
    'ObjectID' => $tokenId,
    'Remarks' => [
        ['Remark' => [
            'RemarkName' => strtoupper(bin2hex('note')),
            'RemarkValue' => strtoupper(bin2hex('sold once')),
        ]],
    ],
]);

$remarks = tokenOf($client, $buyer->getAddress(), $uri)['Remarks'] ?? [];
foreach ($remarks as $remark) {
    note('remark       ' . hex2bin($remark['Remark']['RemarkName']) . ' = '
        . hex2bin($remark['Remark']['RemarkValue'] ?? ''));
}

heading('Burning it');

submit($client, $buyer, [
    'TransactionType' => 'URITokenBurn',
    'Account' => $buyer->getAddress(),
    'URITokenID' => $tokenId,
]);

say('Gone: ' . (tokenOf($client, $buyer->getAddress(), $uri) === null ? 'the token is no longer on the ledger.' : 'it is somehow still there.'));
