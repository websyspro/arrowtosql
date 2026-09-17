<?php

namespace Websyspro\ArrowToSql\Types;

class ColumnTimeStamp 
extends ColumnAbstract
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
