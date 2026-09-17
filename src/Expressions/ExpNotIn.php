<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpNotIn
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}
