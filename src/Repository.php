<?php

namespace Websyspro\ArrowToSql;

use Closure;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;

class Repository
{
  public WhereResult $whereResult;
  public ColumnResult $selectResult;

  public function __construct(
    private readonly string $entity
  ){}

  public function where(
    Closure $closure
  ): Repository {
    $this->whereResult = (
      new ArrowToSQL($closure)
    )->getWhereResult();
    
    return $this;
  }

  public function select(
    Closure $closure
  ): Repository {
    $this->selectResult = (
      new ArrowToSQL($closure)
    )->getColumnResult();
    
    return $this;
  }  
}