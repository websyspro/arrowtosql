<?php

namespace Websyspro\ArrowToSql;

use Closure;

class EntityCreate
{
  public function __construct(
    public readonly string $entity,
  ){}

  public function append(
    Closure|array $closureOrArray
  ): void {
    
  }
}