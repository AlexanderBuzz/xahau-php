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

use Hardcastle\XRPL_PHP\Models\BaseRequest;

/**
 * Which amendments the network knows, and which of them are in force.
 *
 * Worth asking before relying on a transaction type. Xahau's definitions carry
 * the types of several XRP Ledger amendments that are not enabled on it, so
 * the codec will encode a transaction the network then rejects - and a
 * rejection like that is easy to mistake for a defect in this package.
 *
 * xrpl_php has no request object for this command; it is not Xahau-specific
 * and would be equally at home there.
 *
 * https://xrpl.org/feature.html
 */
class FeatureRequest extends BaseRequest
{
    protected string $command = 'feature';

    public function __construct(public ?string $feature = null)
    {
    }
}
