<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectCount 
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $column,
    public array $childs = []
  ){
    parent::__construct();
  }
}