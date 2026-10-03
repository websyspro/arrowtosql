<?php

namespace Websyspro\ArrowToSql;

class EntityUpdate
{
  public function __construct(
    public readonly string $entity
  ){}
}