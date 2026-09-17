<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpDenying 
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}