<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $name = ''
    ) {}
}
