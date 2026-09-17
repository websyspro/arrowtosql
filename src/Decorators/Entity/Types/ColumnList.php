<?php

namespace Websyspro\Server\Decorators\Entity\Types;

use Closure;

class ColumnList 
extends ColumnType
{
  public function any(
    Closure $arrow
  ): void {}

  public function all(
    Closure $arrow
  ): void {}

  public function none(
    Closure $arrow
  ): void {}
}
