<?php declare(strict_types=1);
/**
 * Publishing the bootloader of a Progressive Web App on the ledger.
 *
 * AccountSet takes a small HTML document, up to 4096 bytes of valid UTF-8.
 * There is no reserve for it; it costs one drop of fee per byte, and the
 * client asks the node for that fee. An empty value removes it again.
 * Needs the amendment PWALoader.
 *
 * https://github.com/Xahau/xahaud/releases/tag/2026.10.13-release%2B4119
 */

require __DIR__ . '/bootstrap.php';

$client = xahauClient();
$wallet = fundedWallet($client);

heading('Publishing a bootloader');

$html = '<!DOCTYPE html><html><body>Hello from the ledger</body></html>';
note('bytes        ' . strlen($html));

submit($client, $wallet, [
    'TransactionType' => 'AccountSet',
    'Account' => $wallet->getAddress(),
    'AppLoader' => strtoupper(bin2hex($html)),
]);

heading('And taking it off again');

submit($client, $wallet, [
    'TransactionType' => 'AccountSet',
    'Account' => $wallet->getAddress(),
    'AppLoader' => '',
]);
