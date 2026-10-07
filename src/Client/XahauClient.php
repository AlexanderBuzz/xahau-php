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
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest;
use Hardcastle\Xahau_PHP\Xahau;

/**
 * A connection to a Xahau node.
 *
 * Three of the six collaborators xrpl_php 2.3.0 made replaceable are replaced:
 * the autofiller, because NetworkID is mandatory here and the fee is asked of
 * the node rather than computed; the fee calculator it asks; and the faucet,
 * whose host xrpl_php cannot infer from a Xahau URL. The rest - submitting,
 * reading balances, the order book - is the same on both networks and is
 * inherited untouched.
 *
 * Overriding the getters reaches every path that autofills, including the
 * indirect ones: submitAndWait() goes through the Submitter, which asks the
 * client for its autofiller rather than making one of its own.
 */
class XahauClient extends JsonRpcClient
{
    private ?int $networkId;

    /**
     * @param string $connectionUrl A URL, or one of the short names 'mainnet',
     *   'testnet', 'xahau_mainnet', 'xahau_testnet'
     * @param float|null $feeCushion Unused on Xahau, where the node prices the transaction
     * @param string|null $maxFeeXrp
     * @param float $timeout
     * @param Definitions|null $definitions Defaults to the bundled Xahau set
     * @param int|null $networkId Pin it, rather than have it read from the node
     * @throws Exception
     */
    public function __construct(
        string $connectionUrl = Xahau::MAINNET,
        ?float $feeCushion = null,
        ?string $maxFeeXrp = null,
        float $timeout = 3.0,
        ?Definitions $definitions = null,
        ?int $networkId = null
    ) {
        $this->networkId = $networkId;

        parent::__construct(
            self::resolveConnectionUrl($connectionUrl),
            $feeCushion,
            $maxFeeXrp,
            $timeout,
            $definitions ?? Xahau::definitions()
        );
    }

    /**
     * The short names this package understands, resolved here rather than by
     * xrpl_php: its network table is the XRP Ledger's, and from 3.0.0 on it
     * carries no Xahau entries. A string that is not a short name is taken to
     * be a URL and passed through.
     *
     * @param string $connection
     * @return string
     */
    private static function resolveConnectionUrl(string $connection): string
    {
        return match (strtolower($connection)) {
            'mainnet', 'xahau_mainnet', 'xahau' => Xahau::MAINNET,
            'testnet', 'xahau_testnet' => Xahau::TESTNET,
            default => $connection,
        };
    }

    /**
     * The NetworkID every transaction on this connection carries.
     *
     * Read from the node once and kept, rather than guessed from the URL: a
     * wrong NetworkID does not fail loudly, it produces a transaction that is
     * valid for a different network and is simply not accepted by this one.
     * Pass it to the constructor to avoid the request.
     *
     * @return int
     * @throws Exception
     */
    public function getNetworkId(): int
    {
        if (!is_null($this->networkId)) {
            return $this->networkId;
        }

        $response = $this->request(new ServerInfoRequest())->wait();

        if ($response instanceof ErrorResponse) {
            throw new Exception("getNetworkId: {$response->getError()}");
        }

        $networkId = $response->getResult()['info']['network_id'] ?? null;

        if (is_null($networkId)) {
            throw new Exception(
                'getNetworkId: the node did not report a network_id. Pass one to the constructor.'
            );
        }

        return $this->networkId = (int)$networkId;
    }

    public function getAutofiller(): XahauAutofiller
    {
        return new XahauAutofiller($this);
    }

    public function getFeeCalculator(): XahauFeeCalculator
    {
        return new XahauFeeCalculator($this);
    }

    public function getFaucet(): XahauFaucet
    {
        return new XahauFaucet($this);
    }
}
