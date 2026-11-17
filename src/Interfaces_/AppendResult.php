<?php

namespace Websyspro\ArrowToSql\Interfaces;

class AppendResult
{
  public function __construct(
    public readonly string $values,
    public readonly array $params
  ){}  
}