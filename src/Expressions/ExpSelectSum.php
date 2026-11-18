<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectSum 
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