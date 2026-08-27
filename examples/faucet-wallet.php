<?php declare(strict_types=1);
/**
 * Getting an account with money on it.
 *
 * xrpl_php cannot work out a Xahau faucet on its own: it looks for the
 * substring 'testnet' in the connection URL, and the Xahau testnet is at
 * xahau-test.net - which contains 'test.net'. XahauFaucet supplies the host,
 * and refuses to name one for mainnet rather than pointing at a testnet.
 *
 * Note that the wallet comes back carrying Xahau's definitions. A wallet built
 * with Wallet::generate() would sign against the XRP Ledger's, and that holds
 * up until the first field the two networks do not share.
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\Xahau_PHP\Client\XahauFaucet;

$client = xahauClient();

heading('Where the faucet is');

note("connection   {$client->getConnectionUrl()}");
note("faucet       {$client->getFaucet()->faucetHost()}" . XahauFaucet::FAUCET_PATH);

heading('Asking it');

$wallet = fundedWallet($client);

note("address      {$wallet->getAddress()}");
note("seed         {$wallet->getSeed()}");
note('balance      ' . $client->getXrpBalance($wallet->getAddress()) . ' XAH');

warn('Export that seed to run the other examples without asking the faucet again:');
note("    export XAHAU_TEST_SEED={$wallet->getSeed()}");
