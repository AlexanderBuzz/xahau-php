<?php declare(strict_types=1);
/**
 * Why Xahau needs its own fee model.
 *
 * On the XRP Ledger a transaction costs the base fee times the load factor
 * times a cushion - a number about the network, not about the transaction. On
 * Xahau it is the other way round: a transaction sets off the hooks of the
 * sender and of the receiver, up to four each, and the originator pays for all
 * of them. So the node is asked, with the transaction attached.
 *
 * Nothing here is submitted. Autofilling is enough to show it.
 */

require __DIR__ . '/bootstrap.php';

$client = xahauClient();
$wallet = fundedWallet($client);
$account = $wallet->getAddress();

heading('The same transaction type, three sizes');

foreach ([
    'nothing but the transaction' => [],
    'a 64 byte payload' => ['Blob' => str_repeat('AB', 64)],
    'a 512 byte payload' => ['Blob' => str_repeat('AB', 512)],
] as $label => $extra) {
    $filled = $client->autofill(['TransactionType' => 'Invoke', 'Account' => $account] + $extra);

    note(str_pad($label, 32) . str_pad($filled['Fee'] . ' drops', 16));
}

warn('The XRP Ledger formula would put the same number on all three.');

heading('And what a hook costs');

/*
 * A hook is charged at 10,000 drops per byte of web assembly, which is three
 * orders of magnitude above an ordinary transaction. This is the case the
 * cushion formula is not merely imprecise about but wrong about.
 */
$acceptHook = '0061736D0100000001130360027F7F017F60037F7F7E017E60017F017E02170203656E76025F670000'
    . '03656E760661636365707400010302010205030100020621057F01418088040B7F004180080B7F004180080B7F0041808804'
    . '0B7F004180080B07080104686F6F6B00020A9D80000199800000410141011080808080001A4100410042001081808080000B';

$filled = $client->autofill([
    'TransactionType' => 'SetHook',
    'Account' => $account,
    'Hooks' => [
        ['Hook' => [
            'CreateCode' => $acceptHook,
            'HookApiVersion' => 0,
            'HookOn' => str_repeat('00', 32),
            'HookNamespace' => str_repeat('00', 32),
        ]],
    ],
]);

note(str_pad(strlen($acceptHook) / 2 . ' bytes of web assembly', 32) . "{$filled['Fee']} drops");

heading('The two questions, kept apart');

note('getFeeXrp()             ' . $client->getFeeCalculator()->getFeeXrp() . ' XAH   - what the network charges at all');
note('getFeeEstimateDrops()   see above         - what this transaction costs');
