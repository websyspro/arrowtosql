<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Repository;
use Websyspro\ArrowToSql\RepositoryInRuntime;

class ExpSelectSubQuery 
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $column,
    public string $columnAs,
    public string $tableSubQuery,
    public RepositoryInRuntime $repositoryInRuntime
  ){
    parent::__construct();
  }
}