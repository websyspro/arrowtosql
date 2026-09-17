<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpUnary 
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}