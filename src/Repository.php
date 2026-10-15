<?php

namespace Websyspro\ArrowToSql;

use ReflectionClass;
use Websyspro\ArrowToSql\Interfaces\AppendResultList;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use function is_array;
use function sprintf;
use Exception;
use Closure;

class Repository
{
  public WhereResult $whereResult;
  public ColumnResult $selectResult;
  public ColumnResult $groupByResult;
  public ColumnResult $orderByResult;
  public ColumnResult $orderByAscResult;
  public ColumnResult $orderByDescResult;
  public AppendResultList $appendResultLists;
  public MySqlEntityStructure|SqlServerEntityStructure|PostgresEntityStructure|SqLiteEntityStructure $entityStructure;

  public function __construct(
    private readonly string $entity
  ){}

  private function getEntityStructureMatch(
    ReflectionClass $reflectionClass
  ): MySqlEntityStructure|SqlServerEntityStructure|PostgresEntityStructure|SqLiteEntityStructure {
    return match( Database::driver()){
      DriverType::SqlServer => new SqlServerEntityStructure( $reflectionClass ),
      DriverType::Postgres => new PostgresEntityStructure( $reflectionClass ),
      DriverType::SqLite => new SqLiteEntityStructure( $reflectionClass ),
      DriverType::MySql => new MySqlEntityStructure( $reflectionClass ),
    };   
  } 

  public function getEntityStructure(
  ): MySqlEntityStructure|SqlServerEntityStructure|PostgresEntityStructure|SqLiteEntityStructure {
    if( isset( $this->entityStructure ) === false ){
      $reflectionClass = new ReflectionClass( $this->entity );
      if( $reflectionClass instanceof ReflectionClass ){
        $this->entityStructure = $this->getEntityStructureMatch( $reflectionClass );
      }
    }

    return $this->entityStructure;
  }  

  public function getEntityStructureByEntity(
    string $entity
  ): MySqlEntityStructure|SqlServerEntityStructure|PostgresEntityStructure|SqLiteEntityStructure {
    $reflectionClass = new ReflectionClass( $entity );
    return $this->getEntityStructureMatch( $reflectionClass );;      
  }  

  private function arrowToSql(
    Closure|array $closure
  ): ArrowToSql {
    return new ArrowToSQL(
      $closure, $this
    );
  }
  
  public function leftJoin(
    Closure $closure
  ): Repository {
    return $this;
  }

  public function innerJoin(
    Closure $closure
  ): Repository {
    return $this;
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
    $this->orderByAscResult = $this->arrowToSql($closure)->getColumnResult();
    return $this;
  }

  public function orderByDesc(
    Closure $closure
  ): Repository {
    $this->orderByDescResult = $this->arrowToSql($closure)->getColumnResult();
    return $this;
  }

  public function create(
    Closure|array $closureOrListArr
  ): bool {
    $this->appendResultLists = is_array( $closureOrListArr ) === false
      ? $this->arrowToSql( $closureOrListArr )->getAppendResult()
      : $this->arrowToSql( $closureOrListArr )->getAppendResultFromArray( 
          $this->entity, $closureOrListArr
        );
    
    try {
      foreach( $this->appendResultLists->values as $key => $values ){
        if( Database::driverIsSqlServer() ){
          Database::setIdentityInsert( $this->appendResultLists->table, true );
        }
        
        Database::execute( 
          sprintf( "Insert into %s (%s) values{$values}", 
            $this->appendResultLists->table, 
            $this->appendResultLists->fields
          ), $this->appendResultLists->params[ $key ]
        );

        if( Database::driverIsSqlServer()){
          Database::setIdentityInsert( $this->appendResultLists->table, false );
        }
      }  

      return true;
    } catch( Exception $exception ){}      
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
  ): mixed {
    // var_dump( sprintf( 
    //   "select %s from Document where %s", 
    //     $this->selectResult->script,
    //     $this->whereResult->script
    // ));

    return Database::query(
      sprintf( 
        "select %s from Document where %s", 
          $this->selectResult->script,
          $this->whereResult->script
      ), $this->whereResult->params
    );
  }
}