<?php

namespace Websyspro\ArrowToSql\Interfaces;

class IncludeResult
{
  public function __construct(
    public readonly array|string $script
  ){}
}