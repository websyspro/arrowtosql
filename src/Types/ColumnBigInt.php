<?php

namespace Websyspro\ArrowToSql\Types;

class ColumnBigInt 
extends ColumnAbstract
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
