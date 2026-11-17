<?php

namespace Websyspro\ArrowToSql\Interfaces;

class WhereResult
{
  public function __construct(
    public readonly array|string $script,
    public readonly array $params
  ){}
}