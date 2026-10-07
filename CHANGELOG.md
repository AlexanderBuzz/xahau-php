# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

The first release. Everything below is what 1.0.0 ships with.

### Added
- The fifteen Xahau-only transaction types as models: `SetHook`, the five
  `URIToken*` types, `Remit`, `SetRemarks`, `Invoke`, `Import`, `ClaimReward`,
  `CronSet`, and the pseudo transactions `Cron`, `GenesisMint`, `UNLReport`.
  Eleven of them used to live in `hardcastle/xrpl_php` under `src/Hooks/` and
  leave it with its 3.0.0; `Remit`, `SetRemarks` and the two Cron types had no
  model anywhere. `XahauTransaction` is their base class and carries Xahau's
  six extra common fields.
- Xahau's own `definitions.json`, captured from a Xahau node's
  `server_definitions` with the node's digest, and `Xahau::definitions()`,
  `Xahau::codec()`, `Xahau::wallet()` and `Xahau::client()` to drive
  xrpl_php's codec, wallet and client with it. `Xahau::useDefinitions()` takes
  a set fetched at runtime.
- `XahauClient`, xrpl_php's `JsonRpcClient` with three collaborators replaced:
  the autofiller fills in `NetworkID` and asks the node what the transaction
  costs (`getFeeEstimateDrops()`), the fee calculator does the asking, and the
  faucet knows the Xahau testnet and the JSHooks devnet hosts. The short names
  `mainnet`, `testnet`, `xahau_mainnet`, `xahau_testnet` resolve here, not in
  xrpl_php.
- `ServerDefinitionsRequest` and `FeatureRequest`, with their responses.
- Seven examples against the testnet, and an integration suite that mints,
  sells, buys, remarks on and burns a URIToken, installs and removes a hook,
  creates an account with a `Remit`, and checks the bundled definitions
  against the node.
- `DefinitionsConformanceTest` holds every model to the definitions, and
  `SeamTest` holds the seam: shared types encode with Xahau's ordinals, nested
  structures keep the definitions, signing and hashing run through them, and
  none of it leaks into xrpl_php's default instance.

Requires `hardcastle/xrpl_php` `^2.3 || ^3.0`: the injectable definitions of
2.1.0 and the replaceable collaborators of 2.3.0 are all it needs, and 3.0.0
only removes what this package replaces.
