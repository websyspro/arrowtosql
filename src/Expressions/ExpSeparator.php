<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSeparator 
extends AbstractExpType
{  
  public function __construct(
    public string $value
  ){
    parent::__construct();
  }
}