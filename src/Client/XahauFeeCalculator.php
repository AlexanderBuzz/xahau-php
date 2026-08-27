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

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Hardcastle\XRPL_PHP\Client\FeeCalculator;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

/**
 * What a transaction costs on Xahau.
 *
 * The XRP Ledger's model - base fee times load factor times a cushion - does
 * not apply here. A transaction sets off the hooks of the sender and of the
 * receiver, up to four each, and the originator pays for all of them; the cost
 * is a property of the transaction, not of the network's current load. So the
 * node is asked, with the transaction attached.
 *
 * getFeeXrp() is inherited unchanged and still answers the network's base fee
 * in XRP. It is the right answer to a different question, and the two are kept
 * apart by their names: this one returns drops, which is what a Fee field
 * holds.
 */
class XahauFeeCalculator extends FeeCalculator
{
    /**
     * The parent keeps the client to itself, so this holds its own reference.
     * Both point at the same object; widening the visibility in xrpl_php would
     * make this line unnecessary.
     */
    protected readonly JsonRpcClient $client;

    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;

        parent::__construct($client);
    }

    /**
     * What the node says this transaction costs, in drops.
     *
     * The blob is the transaction as it will be submitted, but unsigned and
     * with Fee set to zero - see XahauAutofiller, which prepares it.
     *
     * Multi-signing is priced differently: the network's own open ledger fee
     * plus one hookless base fee per signature. A signature does not set off a
     * hook, so paying the hook price per signer would overcharge.
     *
     * @param string $txBlob The unsigned transaction, hex encoded
     * @param int $signersCount Number of signatures a multi-signed transaction will carry
     * @return string The fee in drops
     * @throws Exception
     */
    public function getFeeEstimateDrops(string $txBlob, int $signersCount = 0): string
    {
        $response = $this->client->request(new XahauFeeRequest($txBlob))->wait();

        if ($response instanceof ErrorResponse) {
            throw new Exception("getFeeEstimateDrops: {$response->getError()}");
        }

        $drops = $response->getResult()['drops'] ?? [];

        if (!isset($drops['base_fee'])) {
            throw new Exception('getFeeEstimateDrops: could not read drops.base_fee from fee');
        }

        $fee = BigDecimal::of((string)$drops['base_fee']);

        if ($signersCount > 0) {
            if (!isset($drops['open_ledger_fee'], $drops['base_fee_no_hooks'])) {
                throw new Exception(
                    'getFeeEstimateDrops: multi-signing needs drops.open_ledger_fee and drops.base_fee_no_hooks'
                );
            }

            $fee = BigDecimal::sum(
                BigDecimal::of((string)$drops['open_ledger_fee']),
                BigDecimal::of((string)$drops['base_fee_no_hooks'])->multipliedBy(1 + $signersCount)
            );
        }

        return (string)$fee->toScale(0, RoundingMode::Ceiling);
    }
}
