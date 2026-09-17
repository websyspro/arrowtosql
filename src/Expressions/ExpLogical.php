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
}