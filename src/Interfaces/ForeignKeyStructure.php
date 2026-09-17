<?php

namespace Websyspro\ArrowToSql\Interfaces;

class ForeignKeyStructure
{
  public function __construct(
    public readonly EntityNames $entity,
    public readonly string $references
  ){}
}