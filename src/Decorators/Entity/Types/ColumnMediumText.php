<?php

namespace Websyspro\Server\Decorators\Entity\Types;

class ColumnMediumText 
extends ColumnType
{
  public function equals(
    string $value
  ): void {}

  public function in(
    mixed ...$value
  ): void {}
  
  public function notIn(
    mixed ...$value
  ): void {}  

  public function contains(
    mixed ...$value
  ): void {}

  public function startsWith(
    mixed ...$value
  ): void {}

  public function endsWith(
    mixed ...$value
  ): void {}

  public function upper(
    string $value
  ): void {}

  public function lower(
    string $value
  ): void {}
  
  public function trim(
  ): void {}
}
