<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Every example is run, and has to come back with nothing to complain about.
 *
 * Examples that nobody executes rot: an API changes, a network resets, and the
 * snippet in the README goes on looking plausible. Running them costs a couple
 * of minutes against a live testnet, which is exactly why this sits in the
 * integration suite and not in CI.
 *
 * They are given the run's own funded account through XAHAU_TEST_SEED, so that
 * seven examples do not make seven faucet requests - the public faucets allow
 * one per client every 45 seconds and would refuse six of them.
 */
#[Group('integration')]
final class ExamplesTest extends XahauIntegrationTestCase
{
    private const EXAMPLES_DIRECTORY = __DIR__ . '/../../examples';

    /**
     * @return array<string, array{0: string}>
     */
    public static function examples(): array
    {
        $files = glob(self::EXAMPLES_DIRECTORY . '/*.php');
        $cases = [];

        foreach ($files === false ? [] : $files as $file) {
            $name = basename($file, '.php');

            // Not an example; it is what the examples require.
            if ($name === 'bootstrap') {
                continue;
            }

            $cases[$name] = [$file];
        }

        return $cases;
    }

    public function testThereAreExamplesToRun(): void
    {
        $this->assertGreaterThanOrEqual(
            5,
            count(self::examples()),
            'No examples found. Has the directory moved?'
        );
    }

    #[DataProvider('examples')]
    public function testTheExampleRuns(string $file): void
    {
        putenv('XAHAU_TEST_SEED=' . self::primaryWallet()->getSeed());
        putenv('XAHAU_TESTNET_URL=' . self::client()->getConnectionUrl());

        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' 2>&1', $output, $status);

        $this->assertSame(
            0,
            $status,
            basename($file) . " exited with {$status}:" . PHP_EOL . implode(PHP_EOL, array_slice($output, -15))
        );
    }
}
