<?php declare(strict_types=1);
/**
 * Shared setup for the examples.
 *
 * Every example needs the same two things - a client and an account with
 * money on it - and neither is what the example is about, so they live here
 * instead of being repeated seven times.
 *
 * Two environment variables steer this:
 *
 *   XAHAU_TESTNET_URL   the node, default https://xahau-test.net
 *   XAHAU_TEST_SEED     a funded account, so the faucet is left alone
 *
 * Set XAHAU_TEST_SEED if you are going to run several of these in a row: the
 * public faucets allow one request per client every 45 seconds, and refuse the
 * rest.
 */

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\Xahau_PHP\Client\XahauClient;
use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

function xahauClient(): XahauClient
{
    $url = getenv('XAHAU_TESTNET_URL');

    return Xahau::client(
        is_string($url) && $url !== '' ? $url : Xahau::TESTNET,
        Xahau::TESTNET_NETWORK_ID
    );
}

/**
 * An account with money on it: the one named by XAHAU_TEST_SEED, or a fresh
 * one from the faucet.
 */
function fundedWallet(XahauClient $client): Wallet
{
    $seed = getenv('XAHAU_TEST_SEED');
    if (is_string($seed) && $seed !== '') {
        say('Using the account from XAHAU_TEST_SEED.');

        return Xahau::wallet($seed);
    }

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        try {
            $funded = $client->getFaucet()->fundWallet();
            say("Faucet: {$funded['wallet']->getAddress()} now holds {$funded['balance']} XAH.");

            return $funded['wallet'];
        } catch (Exception $exception) {
            warn("Faucet attempt {$attempt} failed - waiting out the cooldown.");
            sleep(25);
        }
    }

    throw new Exception('The faucet would not hand out a wallet. Set XAHAU_TEST_SEED instead.');
}

/**
 * Submit, wait for the ledger to be sure, and print how it went.
 */
function submit(XahauClient $client, Wallet $wallet, array $tx): array
{
    $result = $client->submitAndWait($tx, true, false, $wallet)->getResult();
    $code = $result['meta']['TransactionResult'] ?? 'no result';

    $line = str_pad($tx['TransactionType'], 26) . str_pad($code, 18) . "{$result['Fee']} drops";

    if ($code === 'tesSUCCESS') {
        say($line);
    } else {
        warn($line);
    }

    return $result;
}

function heading(string $text): void
{
    print_r(PHP_EOL . Color::WHITE . $text . PHP_EOL . str_repeat('-', strlen($text)) . Color::RESET . PHP_EOL);
}

function say(string $text): void
{
    print_r(Color::GREEN . $text . Color::RESET . PHP_EOL);
}

function note(string $text): void
{
    print_r(Color::CYAN . $text . Color::RESET . PHP_EOL);
}

function warn(string $text): void
{
    print_r(Color::YELLOW . $text . Color::RESET . PHP_EOL);
}
