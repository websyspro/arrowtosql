<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSubQuery 
extends AbstractExpType
{  
  public function __construct(
    public string $method,
    public string $table,
    public array $childs = []
  ){
    parent::__construct();
  }
}