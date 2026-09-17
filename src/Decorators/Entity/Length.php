<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length
{
    public function __construct(
        public readonly int $value = 255
    ) {}
}
