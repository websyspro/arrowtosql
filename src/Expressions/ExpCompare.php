<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpCompare 
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}