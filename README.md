# Xahau-PHP

PHP SDK for the [Xahau Network](https://xahau.network), built on
[hardcastle/xrpl_php](https://github.com/AlexanderBuzz/xrpl-php).

> **Status: types and client are here.** All fifteen Xahau-only types
> are modelled and covered - the eleven that used to live in xrpl_php's
> `src/Hooks/`, plus `Remit`, `SetRemarks` and the two Cron types that had no
> model anywhere, and `XahauClient` fills in `NetworkID`, asks the node what a
> transaction costs and knows the faucets. An integration suite runs the whole
> thing against the Xahau testnet. Still open before the first release:
> `examples/`.

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

## Tests

```console
composer install
vendor/bin/phpunit --testsuite unit          # offline, this is what CI runs
vendor/bin/phpunit --testsuite integration   # talks to the Xahau testnet
```

The integration suite mints a URIToken, sells it, buys it, remarks on it and
burns it; it creates an account with a `Remit`, and it checks the bundled
definitions against the ones the node reports. Two things steer it:

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
