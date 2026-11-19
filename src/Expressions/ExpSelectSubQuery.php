<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\RepositoryInRuntime;

class ExpSelectSubQuery 
extends AbstractExpTypeSelect
{  
  public function __construct(
    public array $closures,
    public RepositoryInRuntime $repositoryInRuntime
  ){
    parent::__construct();
  }
}