<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpField
extends AbstractExpType
{
  public function __construct(
    public string $table,
    public string $column,
    public string $type,
    public array $methods = []
  ){
    parent::__construct();
  }  
}