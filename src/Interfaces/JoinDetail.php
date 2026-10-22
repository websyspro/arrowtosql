<?php

namespace Websyspro\ArrowToSql\Interfaces;

class JoinDetail
{
  public function __construct(
    public string|null $type,
    public string|null $column = null,
    public string|null $alias = null,
    public string|null $aliasKey = null,
    public string|null $aliasReference = null,
    public string|null $aliasReferenceKey = null,
    public array|null $columnsSelecteds = [],  
  ){}
}