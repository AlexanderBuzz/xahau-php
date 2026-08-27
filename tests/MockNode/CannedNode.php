<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\MockNode;

use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\ResponseInterface;

/**
 * A node that answers with whatever the test says, keyed by JSON-RPC method.
 *
 * The client posts every call to the same path, so a mock that routes by URL
 * cannot tell an account_info from a fee. This one reads the method out of the
 * request body:
 *
 *     $server->setDefaultResponse(new CannedNode([
 *         'fee' => ['drops' => ['base_fee' => '4242']],
 *         'account_info' => ['account_data' => ['Sequence' => 23]],
 *     ]));
 *
 * The payloads are the contents of rippled's `result` object; the envelope is
 * added here.
 */
final class CannedNode implements ResponseInterface
{
    /**
     * @param array<string, array> $resultsByMethod
     */
    public function __construct(private readonly array $resultsByMethod)
    {
    }

    public function getRef(): string
    {
        return md5((string)json_encode($this->resultsByMethod));
    }

    public function getBody(RequestInfo $request): string
    {
        $method = self::methodOf($request->getInput());

        return (string)json_encode([
            'result' => ($this->resultsByMethod[$method] ?? ['error' => 'unknownCmd'])
                + ['status' => 'success'],
        ]);
    }

    public function getHeaders(RequestInfo $request): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function getStatus(RequestInfo $request): int
    {
        return 200;
    }

    /**
     * The JSON-RPC method of a request body, or an empty string.
     */
    public static function methodOf(string $body): string
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) && is_string($decoded['method'] ?? null)
            ? $decoded['method']
            : '';
    }

    /**
     * The params of a request body, which is where a JSON-RPC call carries
     * everything the method takes.
     *
     * @return array<string, mixed>
     */
    public static function paramsOf(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) && is_array($decoded['params'][0] ?? null)
            ? $decoded['params'][0]
            : [];
    }
}
