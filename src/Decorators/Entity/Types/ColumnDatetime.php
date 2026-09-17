<?php

namespace Websyspro\Server\Decorators\Entity\Types;

class ColumnDatetime 
extends ColumnType
{
  public function equals(
    string $datetime
  ): void {}

  public function before(
    string $datetime
  ): void {}

  public function after(
    string $datetime
  ): void {}

  public function between(
    string $start, 
    string $end
  ): void {}

  public function toDate(
    string $datetime
  ): void {}
}
