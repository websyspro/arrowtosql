<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpField
extends AbstractExpType
{
  public function __construct(
    public string $table,
    public string $column,
    public string $columnAs,
    public string $columnType,
    public array $closures = []
  ){
    parent::__construct();
  }  
}