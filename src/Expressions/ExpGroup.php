<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpGroup 
extends AbstractExpType
{  
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }
}