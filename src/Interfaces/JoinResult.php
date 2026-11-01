<?php

namespace Websyspro\ArrowToSql\Interfaces;

class JoinResult
{
  public function __construct(
    public array $args = [],
    public array $childs = []
    // public string|null $alias = null,
    // public string|null $aliasKey = null,
    // public string|null $aliasReference = null,
    // public string|null $aliasReferenceKey = null
  ){}
}