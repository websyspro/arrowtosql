<?php

namespace Websyspro\ArrowToSql\Interfaces;

class ColumnDataset
{
  public function __construct(
    public string $name,
    public string $type,
    public string $alias,
    public string $required,
    public string|null $primaryKey = null,
    public string|null $generation = null,
    public string|null $length = null,
    public string|null $precision = null,
    public string|null $scale = null
  ){}
}