<?php

namespace Websyspro\Server\Decorators\Entity;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Synchronize
{
  public function __construct(
    public readonly bool $enable = true
  ){}
}
