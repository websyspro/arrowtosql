<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany
{
    public function __construct(
        public readonly string $entity,
        public readonly string $foreignKey = ''
    ) {}
}
