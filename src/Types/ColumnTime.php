<?php

namespace Websyspro\ArrowToSql\Types;

class ColumnTime 
extends ColumnAbstract
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
