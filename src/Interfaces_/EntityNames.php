<?php

namespace Websyspro\ArrowToSql\Interfaces;

class EntityNames
{
  public function __construct(
    public readonly string $table,
    public readonly string $alias,
  ) {}
}