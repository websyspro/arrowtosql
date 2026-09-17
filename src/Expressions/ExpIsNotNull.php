<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpIsNotNull
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}
