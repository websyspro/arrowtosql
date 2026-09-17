<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpIsNull
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}
