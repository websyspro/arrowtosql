<?php

namespace Websyspro\ArrowToSql\Interfaces;

class RepositoryResult
{
  public function __construct(
    public string $sql,
    public array $includesLinears = [],
    public array $includesRelateds = [],
    public array $rows = []
  ){}
}