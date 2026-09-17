<?php

namespace Websyspro\Server\Decorators\Entity\Types;

class ColumnInteger 
extends ColumnType
{
  public function equals(
    int|float $value
  ): void {}

  public function greaterThan(
    int|float $value
  ): void {}

  public function lessThan(
    int|float $value
  ): void {}

  public function between(
    int|float $min,
    int|float $max
  ): void {}
}
