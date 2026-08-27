<?php declare(strict_types=1);
/**
 * Invoke: a transaction that moves nothing.
 *
 * It exists to set hooks off. Every field is optional - Destination decides
 * whose hooks run besides the sender's own, and Blob carries whatever the hook
 * expects to read.
 *
 * HookParameters is worth noticing: on Xahau it is a common field, not part of
 * Invoke, so any transaction at all can pass values to the hooks it triggers.
 * That is one of six common fields Xahau has and the XRP Ledger does not,
 * which is why every model in this package extends XahauTransaction rather
 * than xrpl_php's BaseTransaction.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/invoke
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\Xahau_PHP\Xahau;

$client = xahauClient();
$sender = fundedWallet($client);

heading('Setting off nothing in particular');

/*
 * The account has no hooks installed, so nothing happens - and that is a
 * perfectly valid transaction. Run set-hook.php first to give it one.
 */
submit($client, $sender, [
    'TransactionType' => 'Invoke',
    'Account' => $sender->getAddress(),
]);

heading('With a payload, addressed at another account');

$other = Xahau::generateWallet();
submit($client, $sender, [
    'TransactionType' => 'Remit',
    'Account' => $sender->getAddress(),
    'Destination' => $other->getAddress(),
    'Amounts' => [['AmountEntry' => ['Amount' => '20000000']]],
]);

$result = submit($client, $sender, [
    'TransactionType' => 'Invoke',
    'Account' => $sender->getAddress(),
    'Destination' => $other->getAddress(),
    'Blob' => strtoupper(bin2hex('anything the hook wants to read')),
    'HookParameters' => [
        ['HookParameter' => [
            'HookParameterName' => strtoupper(bin2hex('mode')),
            'HookParameterValue' => strtoupper(bin2hex('demo')),
        ]],
    ],
]);

note('Blob as the ledger has it   ' . hex2bin($result['Blob']));
note('HookParameterName           ' . hex2bin($result['HookParameters'][0]['HookParameter']['HookParameterName']));

warn('The fee is above the base fee here, because the payload is part of what the node prices.');
