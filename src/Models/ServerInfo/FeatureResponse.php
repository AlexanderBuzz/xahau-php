<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Models\ServerInfo;

use Hardcastle\XRPL_PHP\Models\BaseResponse;

/**
 * The answer to FeatureRequest.
 */
class FeatureResponse extends BaseResponse
{
    /**
     * The amendments that are in force, by name.
     *
     * @return array<string, bool> Amendment name => enabled
     */
    public function getAmendments(): array
    {
        $amendments = [];

        foreach ($this->getResult()['features'] ?? [] as $feature) {
            if (isset($feature['name'])) {
                $amendments[$feature['name']] = (bool)($feature['enabled'] ?? false);
            }
        }

        return $amendments;
    }
}
