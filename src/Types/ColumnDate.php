<?php

namespace Websyspro\ArrowToSql\Types;

class ColumnDate 
extends ColumnAbstract
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
