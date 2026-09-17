<?php

namespace Websyspro\ArrowToSql\Interfaces;

use Websyspro\Server\Commons\Collection;

class EntityDetails
{
  public function __construct(
    public readonly EntityNames $entityNames,
    public readonly Collection $columnDetails
  ){}
}