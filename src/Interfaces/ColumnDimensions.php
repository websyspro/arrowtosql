<?php

namespace Websyspro\ArrowToSql\Interfaces;

class ColumnDimensions
{
  public function __construct(
    public readonly int|null $lengths = null,
    public readonly int|null $precision = null,
    public readonly int|null $scale = null
  ){}
}