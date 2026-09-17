<?php

namespace Websyspro\Server\Decorators\Entity\Types;

class ColumnDate 
extends ColumnType
{
  public function equals(
    string $date
  ): void {}

  public function before(
    string $date
  ): void {}

  public function after(
    string $date
  ): void {}

  public function between(
    string $start, 
    string $end
  ): void {}
}
