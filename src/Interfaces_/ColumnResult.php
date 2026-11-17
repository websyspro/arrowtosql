<?php

namespace Websyspro\ArrowToSql\Interfaces;

class ColumnResult
{
  public function __construct(
    public readonly string|array $script,
    public readonly array $params
  ){}  
}