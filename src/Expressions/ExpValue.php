<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpValue
extends AbstractExpType
{
  public function __construct(
    public array $childs = []
  ){
    parent::__construct();
  }  
}