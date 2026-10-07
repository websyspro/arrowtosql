<?php

namespace Websyspro\ArrowToSql\Interfaces;

class AppendResult
{
  public function __construct(
    public readonly string $table,
    public readonly array $fields,
    public readonly array $params
  ){}  
}