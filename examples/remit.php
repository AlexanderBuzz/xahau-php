<?php declare(strict_types=1);
/**
 * Remit: sending to an account that does not exist yet.
 *
 * A Payment cannot create its destination - the account has to be there, with
 * its reserve already funded by somebody. Remit creates it on the way, which
 * is how an application onboards a user who has nothing at all. It can carry
 * XAH, issued currencies and URITokens in the same transaction.
 *
 * https://docs.xahau.network/features/transaction-types/remit
 */

require __DIR__ . '/bootstrap.php';

use Hardcastle\Xahau_PHP\Xahau;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;

$client = xahauClient();
$sender = fundedWallet($client);

// Generated through the facade, so that it carries Xahau's definitions and can
// sign Xahau-only fields later on.
$newcomer = Xahau::generateWallet();

heading('An address nobody has heard of');

note("address      {$newcomer->getAddress()}");
$exists = !($client->syncRequest(new AccountInfoRequest(account: $newcomer->getAddress())) instanceof
    Hardcastle\XRPL_PHP\Models\ErrorResponse);
note('on ledger    ' . ($exists ? 'yes' : 'no, not yet'));

heading('Remit brings it into being');

submit($client, $sender, [
    'TransactionType' => 'Remit',
    'Account' => $sender->getAddress(),
    'Destination' => $newcomer->getAddress(),
    'Amounts' => [['AmountEntry' => ['Amount' => '100000000']]],   // 100 XAH
    'Blob' => strtoupper(bin2hex('welcome')),
]);

heading('And now it exists');

$accountData = $client->syncRequest(new AccountInfoRequest(account: $newcomer->getAddress()))
    ->getResult()['account_data'];

note("account      {$accountData['Account']}");
note('balance      ' . $client->getXrpBalance($newcomer->getAddress()) . ' XAH');
note("sequence     {$accountData['Sequence']}");

say('The newcomer can transact from here on its own.');
