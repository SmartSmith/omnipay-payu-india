<?php

declare(strict_types=1);

namespace Omnipay\PayUIndia\Tests\Endpoints;

use Attribute;

/**
 * Names the gateway operation an end-to-end test proves on its success path, as `Gateway::<method>`.
 * EveryEntryPointIsCalledTest holds the set to naming each operation once, so removing any one of
 * these tests turns it red. A failure answer's test carries none, so it never stands in for the
 * success path.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Calls
{
    public function __construct(public string $entry) {}
}
