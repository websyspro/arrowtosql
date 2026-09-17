<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpNotLike
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}