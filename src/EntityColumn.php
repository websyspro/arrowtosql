<?php

namespace Websyspro\ArrowToSql;

class EntityColumn
{
  public function __construct(
      public readonly string  $property,
      public readonly string  $column,
      public readonly string  $type,
      public readonly bool    $isPrimaryKey = false,
      public readonly bool    $isIndex      = false,
      public readonly bool    $isUnique     = false,
      public readonly ?string $foreignEntity = null,
      public readonly ?string $foreignColumn = null,
  ) {}
}