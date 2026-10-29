# Xahau-PHP

PHP SDK for the [Xahau Network](https://xahau.network), built on
[hardcastle/xrpl_php](https://github.com/AlexanderBuzz/xrpl-php).

> **Status: ready for 1.0.0.** All fifteen Xahau-only types
> are modelled and covered - the eleven that used to live in xrpl_php's
> `src/Hooks/`, plus `Remit`, `SetRemarks` and the two Cron types that had no
> model anywhere, and `XahauClient` fills in `NetworkID`, asks the node what a
> transaction costs and knows the faucets. An integration suite runs the whole
> thing against the Xahau testnet, and `examples/` covers it end to end.

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
composer require hardcastle/xahau-php
```

## Usage

```php
use Hardcastle\Xahau_PHP\Xahau;

$client = Xahau::client(Xahau::MAINNET);   // or Xahau::TESTNET
$wallet = Xahau::wallet($seed);

$client->submitAndWait([
    'TransactionType' => 'Remit',
    'Account' => $wallet->getAddress(),
    'Destination' => $destination,
], autofill: true, wallet: $wallet);
```

`NetworkID` and the fee are filled in for you. On a test network,
`$client->getFaucet()->fundWallet()` hands back a funded wallet.

`Xahau::codec()` returns a `BinaryCodec` on Xahau's definitions, and
`Xahau::definitions()` the definitions themselves, should you need to pass them
somewhere yourself.

## What the client does differently

`XahauClient` is xrpl_php's `JsonRpcClient` with three of its collaborators
replaced; everything else - submitting, balances, the order book - is shared.

**The fee comes from the node.** A transaction sets off the hooks of the sender
and of the receiver, up to four each, and the originator pays for all of them,
so the cost belongs to the transaction rather than to the network's current
load. The XRP Ledger's base fee times load factor times a cushion is the wrong
model. Instead the transaction is encoded unsigned, with `Fee` at zero, and
handed to the `fee` command, which answers with what that transaction costs.
`getFeeXrp()` still returns the network's base fee in XRP; the Xahau number is
`getFeeEstimateDrops()`, in drops, which is what a `Fee` field holds.

**`NetworkID` is mandatory**, and is filled in before the fee is worked out -
it is part of the transaction the node prices, and part of what gets signed.
Pass it to `Xahau::client()` to save the lookup, otherwise it is read from the
node once.

**The faucets are named explicitly.** xrpl_php infers a faucet from the
connection URL by looking for `testnet`, and the Xahau testnet is at
`xahau-test.net` - `test.net`, not `testnet` - so the lookup finds nothing.
`XahauFaucet` knows `xahau-test.net` and the JSHooks devnet, and refuses to
answer for mainnet rather than pointing at a testnet faucet.

## Transaction types

Xahau-only, modelled here:

| | |
|---|---|
| `SetHook` | installs, updates and removes hooks |
| `URITokenMint`, `URITokenBurn`, `URITokenBuy`, `URITokenCreateSellOffer`, `URITokenCancelSellOffer` | URITokens |
| `Remit`, `SetRemarks` | sending, and remarks on any object |
| `Invoke`, `Import`, `ClaimReward` | hook calls, migration from the XRP Ledger, balance rewards |
| `CronSet` | schedules an alarm that later fires the account's hooks |
| `Cron`, `GenesisMint`, `UNLReport` | pseudo transactions, readable but not submittable |

The 47 types Xahau shares with the XRP Ledger are used straight from
xrpl_php - a model carries only its field list, and the ordinal comes from the
definitions handed to the codec. Being in the definitions is not the same as
being live, though: Xahau inherited the definitions of several XRPL amendments
that are not enabled on it. Check `feature` on a node before relying on one.

Xahau also has six common fields the XRP Ledger does not - `HookParameters`,
`HookName`, `EmitDetails`, `FirstLedgerSequence`, `OperationLimit` and
`PreviousTxnID` - and lacks `Delegate`. `XahauTransaction`, the base class of
every model here, adjusts the shared list accordingly.

## Examples

Seven scripts under `examples/`, each of which runs against the testnet:

| | |
|---|---|
| `client.php` | connecting, and checking the bundled definitions against the node's |
| `fees.php` | why Xahau needs its own fee model - nothing is submitted |
| `faucet-wallet.php` | getting an account with money on it |
| `remit.php` | sending to an account that does not exist yet |
| `uritoken.php` | mint, offer, buy, remark, burn |
| `set-hook.php` | installing a hook and taking it off again |
| `invoke.php` | setting hooks off, with parameters |

```console
php examples/fees.php
```

`fees.php` is the one to read first if you only read one. It autofills the same
transaction type at three sizes and prints what the node charges for each:

```
nothing but the transaction     10 drops
a 64 byte payload               74 drops
a 512 byte payload              522 drops
141 bytes of web assembly       70510 drops
```

The XRP Ledger's formula would put the same number on all four.

Export `XAHAU_TEST_SEED` before running several in a row, or the faucet will
refuse - `faucet-wallet.php` prints the line to copy.

## Security

**PHP2Shell (CVE-2026-109618, CVE-2026-109619), October 2026: this library is not
affected.** Both flaws are in the PHP interpreter's request handling, in the web
SAPIs (PHP-FPM, mod_php, php-cgi, FrankenPHP, LiteSpeed), and trigger before any
application or library code runs. Nothing in this package handles requests,
uploads or sessions, and there is nothing to change here. What has to be
updated is the PHP runtime an application built on it runs under: 8.2.35,
8.3.36, 8.4.27 or 8.5.12 and later carry the fix. Until that is in place, the
PHP project's mitigations (`session.upload_progress.enabled=0`, a low
`post_max_size`, `memory_limit` set through `php_admin_value`) reduce the
exposure but do not remove it.

To report a security issue in this package itself, write to the address in
`composer.json` rather than opening a public issue.

## Tests

```console
composer install
vendor/bin/phpunit --testsuite unit          # offline, this is what CI runs
vendor/bin/phpunit --testsuite integration   # talks to the Xahau testnet
```

The integration suite mints a URIToken, sells it, buys it, remarks on it and
burns it; it installs a hook and takes it off again; it creates an account with
a `Remit`; it checks the bundled definitions against the ones the node reports;
and it runs every example. Two things steer it:

| | |
|---|---|
| `XAHAU_TESTNET_URL` | the node, default `https://xahau-test.net` |
| `XAHAU_TEST_SEED` | a funded wallet, so the faucet is left alone |

Without a seed the faucet is asked once per run and the wallet is shared by
every test - the public faucets allow one request per client every 45 seconds,
and a refusal is reported as *"The faucet did not return an account address"*,
which is worth knowing before it puzzles you. A second account is created by a
`Remit` from the first rather than by asking again.

`SeamTest` is the acceptance test: it checks that shared types are encoded with
Xahau's ordinals, that types both networks share bit for bit stay identical,
that nested arrays keep the definitions, that signing and hashing run through
them, and that none of it leaks into xrpl_php's default instance.
