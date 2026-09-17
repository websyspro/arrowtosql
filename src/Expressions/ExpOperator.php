<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpOperator
extends AbstractExpType
{  
  public function __construct(
    public string $type
  ){
    parent::__construct();
  }
}