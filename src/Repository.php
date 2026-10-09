<?php

namespace Websyspro\ArrowToSql;

use Exception;
use Websyspro\ArrowToSql\Interfaces\AppendResult;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\Connection\Database;
use Websyspro\Utils\Collection;
use function sprintf;
use Closure;

class Repository
{
  public WhereResult $whereResult;
  public ColumnResult $selectResult;
  public ColumnResult $groupByResult;
  public ColumnResult $orderByResult;
  public ColumnResult $orderByAscResult;
  public ColumnResult $orderByDescResult;
  public AppendResult $appendResult;

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
  ): bool {
    $this->appendResult = $this->arrowToSql($closure)
      ->getAppendResult();

    if( $this->appendResult instanceof AppendResult ){
      try {
        Database::query( 
          sprintf( "Insert into %s (%s) values(%s)", 
            $this->appendResult->table, 
            $this->appendResult->fields,
            $this->appendResult->values
          ), $this->appendResult->params
        );

        return true;
      } catch( Exception $exception ){
      }
    }

    return false;
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