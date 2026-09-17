<?php

namespace Websyspro\Server\Decorators\Entity\Types;

class ColumnTime 
extends ColumnType
{
  public function equals(
    string $time
  ): void {}

  public function before(
    string $time
  ): void {}

  public function after(
    string $time
  ): void {}

  public function between(
    string $start, string $end
  ): void {}
}
