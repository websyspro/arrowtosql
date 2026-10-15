<?php

namespace Websyspro\ArrowToSql\Interfaces;

class RepositoryResult
{
  public function __construct(
    public string $sql,
    public array $rows = []
  ){}
}