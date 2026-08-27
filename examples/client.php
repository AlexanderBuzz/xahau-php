<?php declare(strict_types=1);
/**
 * Connecting to Xahau, and what the client knows once it has.
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\Xahau_PHP\Models\ServerInfo\ServerDefinitionsRequest;
use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest;

$client = xahauClient();

heading('The connection');

$info = $client->syncRequest(new ServerInfoRequest())->getResult()['info'];

note("URL             {$client->getConnectionUrl()}");
note("build           {$info['build_version']}");
note("NetworkID       {$client->getNetworkId()}");
note("ledger          {$client->getLedgerIndex()}");

heading('The definitions the codec runs on');

/*
 * The bundled set is a capture of a node's server_definitions. Comparing it
 * against the node being talked to is the only way to notice that it has gone
 * stale - a new amendment shifts the ordinals, and everything keeps encoding
 * happily against the set it was born with.
 */
$live = $client->syncRequest(new ServerDefinitionsRequest())->getResult();
$bundled = Xahau::definitions();

$drifted = [];
foreach ($live['TRANSACTION_TYPES'] as $name => $ordinal) {
    if ($bundled->mapSpecificFieldFromValue('TransactionType', (string)$name) !== $ordinal) {
        $drifted[] = $name;
    }
}

note('transaction types on the node  ' . count($live['TRANSACTION_TYPES']));

if ($drifted === []) {
    say('The bundled definitions agree with the node.');
} else {
    warn('These types differ from the bundled definitions: ' . implode(', ', $drifted));
    warn('Refresh definitions/definitions.json, or hand the live ones to Xahau::useDefinitions().');
}

heading('Following the node instead of the bundle');

/*
 * This is what makes an amendment a non-event for an application: take the
 * definitions from the node at runtime, and nothing has to be released here
 * for a new transaction type to be encodable.
 */
Xahau::useDefinitions(
    $client->syncRequest(new ServerDefinitionsRequest())->getDefinitions()
);

say('Xahau::codec(), Xahau::wallet() and Xahau::client() now use the node\'s own definitions.');
