<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpExists
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $column,
    public mixed $repositoryInRuntime = null
  ){
    parent::__construct();
  }
}
