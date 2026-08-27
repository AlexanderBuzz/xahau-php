<?php declare(strict_types=1);
/**
 * Installing a hook on an account, and taking it off again.
 *
 * A hook is web assembly that the network runs on every transaction the
 * account is party to. This one is the accept hook from xahau.js' own test
 * suite: 141 bytes that let everything through. It does nothing useful, which
 * is the point - what it shows is the shape of the transaction and what it
 * costs.
 *
 * SetHook is the deepest structure Xahau has. Hooks is an array of Hook
 * objects, each of which carries arrays of its own - HookParameters and
 * HookGrants - so it is also the transaction that says the most about whether
 * a binary codec has the definitions right.
 *
 * https://docs.xahau.network/technical/protocol-reference/transactions/transaction-types/sethook
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;

/** hsfOverride: replace whatever is in the slot, including with nothing. */
const HSF_OVERRIDE = 1;

const ACCEPT_HOOK = '0061736D0100000001130360027F7F017F60037F7F7E017E60017F017E02170203656E76025F670000'
    . '03656E760661636365707400010302010205030100020621057F01418088040B7F004180080B7F004180080B7F0041808804'
    . '0B7F004180080B07080104686F6F6B00020A9D80000199800000410141011080808080001A4100410042001081808080000B';

$client = xahauClient();
$wallet = fundedWallet($client);

/**
 * The hooks installed on an account, as the ledger has them.
 */
function hooksOf($client, string $account): array
{
    $objects = $client->syncRequest(new AccountObjectsRequest(account: $account))
        ->getResult()['account_objects'] ?? [];

    foreach ($objects as $object) {
        if (($object['LedgerEntryType'] ?? null) === 'Hook') {
            return $object['Hooks'] ?? [];
        }
    }

    return [];
}

heading('What it will cost');

$tx = [
    'TransactionType' => 'SetHook',
    'Account' => $wallet->getAddress(),
    'Hooks' => [
        ['Hook' => [
            'CreateCode' => ACCEPT_HOOK,
            'HookApiVersion' => 0,
            // A 256 bit mask over the transaction types the hook fires on -
            // not an amount, despite being a Hash256.
            'HookOn' => str_repeat('00', 32),
            'HookNamespace' => str_repeat('00', 32),
            // Xahau-only field. A wallet built without Xahau's definitions
            // cannot sign a transaction carrying it.
            'HookName' => strtoupper(bin2hex('HOOK')),
            'HookParameters' => [
                ['HookParameter' => [
                    'HookParameterName' => strtoupper(bin2hex('greeting')),
                    'HookParameterValue' => strtoupper(bin2hex('hello')),
                ]],
            ],
        ]],
    ],
];

note(strlen(ACCEPT_HOOK) / 2 . ' bytes of web assembly, at 10,000 drops per byte');
note('fee the node names   ' . $client->autofill($tx)['Fee'] . ' drops');
warn('An ordinary transaction on this network costs 10.');

heading('Installing it');

submit($client, $wallet, $tx);

foreach (hooksOf($client, $wallet->getAddress()) as $index => $hook) {
    note("slot {$index}       HookHash {$hook['Hook']['HookHash']}");
}

heading('Taking it off again');

/*
 * An empty CreateCode with hsfOverride empties the slot. Deleting costs the
 * ordinary fee - only creating the web assembly is charged by the byte.
 */
submit($client, $wallet, [
    'TransactionType' => 'SetHook',
    'Account' => $wallet->getAddress(),
    'Hooks' => [['Hook' => ['CreateCode' => '', 'Flags' => HSF_OVERRIDE]]],
]);

say('Hooks left on the account: ' . count(hooksOf($client, $wallet->getAddress())));
