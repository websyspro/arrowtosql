<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpLike
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}