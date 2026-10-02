<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Closure;

class Repository
{
  private WhereResult $whereResult;
  private ColumnResult $selectResult;
  private ColumnResult $groupByResult;
  private ColumnResult $orderByResult;
  private ColumnResult $orderByAscResult;
  private ColumnResult $orderByDescResult;

  public function __construct(
    private readonly string $entity
  ){}

  public function where(
    Closure $closure
  ): Repository {
    $this->whereResult = $this->arrowToSql($closure)
      ->getWhereResult();
    
    return $this;
  }

  public function select(
    Closure $closure
  ): Repository {
    $this->selectResult = $this->arrowToSql($closure)
      ->getColumnResult();
    
    return $this;
  }

  public function groupBy(
    Closure $closure
  ): Repository {
    $this->groupByResult = $this->arrowToSql($closure)
      ->getColumnResult();
    
    return $this;
  }

  public function orderBy(
    Closure $closure
  ): Repository {
    $this->orderByResult = $this->arrowToSql($closure)
      ->getColumnResult();
    
    return $this;
  }
  
  public function orderByAsc(
    Closure $closure
  ): Repository {
    $this->orderByAscResult = $this->arrowToSql($closure)
      ->getColumnResult();
    
    return $this;
  }

  public function orderByDesc(
    Closure $closure
  ): Repository {
    $this->orderByDescResult = $this->arrowToSql($closure)
      ->getColumnResult();
    
    return $this;
  }  
  
  private function arrowToSql(
    Closure $closure
  ): ArrowToSql {
    return new ArrowToSQL($closure);
  }
}