<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class InitialDefault
{
  public function __construct(
    public readonly string $generator = ''
  ){}
}
