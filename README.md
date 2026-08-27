# Xahau-PHP

PHP SDK for the [Xahau Network](https://xahau.network), built on
[hardcastle/xrpl_php](https://github.com/AlexanderBuzz/xrpl-php).

> **Status: skeleton.** It proves that the whole stack works against Xahau's
> definitions and covers a first two transaction types. The remaining Xahau
> types still live in xrpl_php and move here before the first release.

## Why a separate package

Xahau and the XRP Ledger have drifted apart. Of the 59 transaction types both
networks know, only 36 carry the same ordinal — `MPTokenIssuanceCreate` is 54 on
the XRP Ledger and 63 on Xahau, `URITokenMint` sits on the ordinal the XRP
Ledger gives to `XChainAddClaimAttestation`. One shared set of definitions is
therefore wrong for one of the two networks no matter how the overlap is
resolved.

Since xrpl_php 2.1.0 the codec accepts definitions from outside, so the entire
XRP Ledger stack — codec, wallet, client, signing, hashing — can be driven with
Xahau's own set. This package supplies that set and the Xahau specific
transaction types; everything else comes from xrpl_php.

## Installation

```console
composer require hardcastle/xahau_php
```

## Usage

```php
use Hardcastle\Xahau_PHP\Xahau;

$client = Xahau::client(Xahau::MAINNET);   // or Xahau::TESTNET
$wallet = Xahau::wallet($seed);

$tx = [
    'TransactionType' => 'Remit',
    'Account' => $wallet->getAddress(),
    'Destination' => $destination,
    'NetworkID' => Xahau::MAINNET_NETWORK_ID,
];

$signed = $wallet->sign($client->autofill($tx));
$client->submitAndWait($signed['tx_blob']);
```

`Xahau::codec()` returns a `BinaryCodec` on Xahau's definitions, and
`Xahau::definitions()` the definitions themselves, should you need to pass them
somewhere yourself.

`NetworkID` is required by Xahau on every transaction.

## Tests

```console
composer install
vendor/bin/phpunit
```

`SeamTest` is the acceptance test: it checks that shared types are encoded with
Xahau's ordinals, that types both networks share bit for bit stay identical,
that nested arrays keep the definitions, that signing and hashing run through
them, and that none of it leaks into xrpl_php's default instance.
