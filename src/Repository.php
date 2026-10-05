<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Closure;
use Websyspro\Utils\Collection;

class Repository
{
  public WhereResult $whereResult;
  public ColumnResult $selectResult;
  public ColumnResult $groupByResult;
  public ColumnResult $orderByResult;
  public ColumnResult $orderByAscResult;
  public ColumnResult $orderByDescResult;

  public ColumnResult $createResult;

  public function __construct(
    private readonly string $entity
  ){}

  private function arrowToSql(
    Closure $closure
  ): ArrowToSql {
    return new ArrowToSQL($closure);
  }  

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

  public function create(
    Closure $closure
  ): mixed {
    $this->createResult = $this->arrowToSql($closure)
      ->getAppendResult();

    return $this;      
  }

  public function update(
    Closure $closure
  ): mixed {
    return [];
  }  

  public function delete(
    Closure $closure
  ): mixed {
    return [];
  }  

  public function row(
  ): mixed {
    return [];
  }
  
  public function rows(
  ): Collection {
    return new Collection();
  }
}