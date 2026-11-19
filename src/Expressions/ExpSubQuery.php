<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSubQuery 
extends AbstractExpType
{  
  public function __construct(
    public string $name,
    public mixed $repositoryInRuntime = null
  ){
    parent::__construct();
  }
}