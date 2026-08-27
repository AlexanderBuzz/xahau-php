<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Client;

use Exception;
use Hardcastle\XRPL_PHP\Client\Autofiller;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

/**
 * Fills in what a Xahau transaction needs, where that differs from the XRP
 * Ledger.
 *
 * Two things do. NetworkID is mandatory here and has to be in place before the
 * fee is worked out, because it is part of the transaction the node prices.
 * And the fee itself is asked of the node rather than computed - see
 * XahauFeeCalculator.
 *
 * Everything else - the sequence, LastLedgerSequence, X-addresses, the empty
 * tag cleanup - is inherited.
 */
class XahauAutofiller extends Autofiller
{
    /**
     * The parent keeps the client to itself, so this holds its own reference.
     * Typed as XahauClient rather than JsonRpcClient, because the fee comes
     * from the Xahau fee calculator.
     */
    protected readonly XahauClient $client;

    public function __construct(XahauClient $client)
    {
        $this->client = $client;

        parent::__construct($client);
    }

    /**
     * Put NetworkID in place, then let the parent do the rest.
     *
     * Order matters: the parent calculates the fee from the transaction as it
     * stands, and on Xahau that means encoding it and handing it to the node.
     * A NetworkID added afterwards would be missing from the blob that was
     * priced, and would also change the transaction that gets signed.
     *
     * A NetworkID already set is left alone, so a caller can pin it.
     *
     * @param Transaction|string|array $transaction A model, a tx_blob or an array
     * @param int|null $signersCount Number of signatures a multi-signed transaction will carry
     * @return array
     * @throws Exception
     */
    public function autofill(Transaction|string|array $transaction, ?int $signersCount = null): array
    {
        $tx = $this->toArray($transaction);

        if (!isset($tx['NetworkID'])) {
            $tx['NetworkID'] = $this->client->getNetworkId();
        }

        return parent::autofill($tx, $signersCount);
    }

    /**
     * Ask the node what this transaction costs.
     *
     * The blob it is asked about is the transaction as it will be submitted,
     * minus the two fields that cannot be known yet: the signature key is
     * emptied and the fee zeroed, which is the form the fee command expects.
     * Both are set on a copy, so the transaction being autofilled keeps
     * whatever the caller put there.
     *
     * @param array $tx
     * @param int|null $signersCount
     * @return void
     * @throws Exception
     */
    public function calculateFeePerTransactionType(array &$tx, ?int $signersCount = 0): void
    {
        $unsigned = $tx;
        $unsigned['SigningPubKey'] = '';
        $unsigned['Fee'] = '0';

        $blob = (new BinaryCodec($this->client->getDefinitions()))->encode($unsigned);

        $tx['Fee'] = $this->client->getFeeCalculator()->getFeeEstimateDrops($blob, $signersCount ?? 0);
    }

    /**
     * The three forms autofill() accepts, as an array.
     *
     * @param Transaction|string|array $transaction
     * @return array
     * @throws Exception
     */
    private function toArray(Transaction|string|array $transaction): array
    {
        if (is_string($transaction)) {
            return (new BinaryCodec($this->client->getDefinitions()))->decode($transaction);
        }

        if ($transaction instanceof Transaction) {
            return $transaction->toArray();
        }

        return $transaction;
    }
}
