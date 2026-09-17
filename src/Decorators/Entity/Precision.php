<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Precision
{
    public function __construct(
        public readonly int $precision = 10,
        public readonly int $scale     = 2
    ) {}
}
