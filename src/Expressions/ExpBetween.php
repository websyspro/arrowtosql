<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpBetween
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}