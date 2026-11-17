<?php

namespace Websyspro\ArrowToSql\Interfaces;

class ColumnEntity
{
  public function __construct(
    public string $name,
    public string $type,
    public string|null $alias = null,
    public bool|null $required = null,
    public bool|null $primaryKey = null,
    public bool|null $generation = null,
    public ColumnDimensions|null $columnDimensions = null,
  ){}
}