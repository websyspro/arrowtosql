<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpLogical 
extends AbstractExpType
{  
  public function __construct(
    public string $value
  ){
    parent::__construct();
  }

  public function get(
  ): string {
    return $this->value; 
  }  
}