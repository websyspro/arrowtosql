<?php

namespace Websyspro\ArrowToSql;

use ReflectionClass;
use Websyspro\ArrowToSql\Interfaces\AppendResultList;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\RepositoryResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Connection\Database;
use function is_array;
use function sprintf;
use Exception;
use Closure;

class Repository
{
  private int $offSet = 0;
  private int $limit = -1;
  private array $joins = [];
  private static array $cacheES = [];
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
    if( isset( static::$cacheES[ $this->entity ])){
      return static::$cacheES[ $this->entity ];
    }

    if( isset( $this->entityStructure ) === false ){
      $reflectionClass = new ReflectionClass( $this->entity );
      if( $reflectionClass instanceof ReflectionClass ){
        static::$cacheES[ $this->entity ] = $this->getEntityStructureMatch( $reflectionClass );
      }
    }

    return static::$cacheES[ $this->entity ];
  }  

  public function getEntityStructureByEntity(
    string $entity
  ): MySqlEntityStructure|SqlServerEntityStructure|PostgresEntityStructure|SqLiteEntityStructure {
    if( isset( static::$cacheES[ $entity ])){
      return static::$cacheES[ $entity ];
    }

    $reflectionClass = new ReflectionClass( $entity );
    static::$cacheES[ $entity ] = $this->getEntityStructureMatch( $reflectionClass );
    return static::$cacheES[ $entity ];
  }  

  private function arrowToSql(
    Closure|array $closure
  ): ArrowToSql {
    return new ArrowToSQL(
      $closure, $this
    );
  }

  public function RightJoin(
    Closure $closure
  ): Repository {
    $this->joins[] = new JoinArrowToSql( $closure );
    return $this;
  }  
  
  public function leftJoin(
    Closure $closure
  ): Repository {
    $this->joins[] = new JoinArrowToSql( $closure );
    return $this;
  }

  public function innerJoin(
    Closure $closure
  ): Repository {
    $this->joins[] = new JoinArrowToSql( $closure );
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

  private function definePaged(
  ): string|null {
    if( $this->limit === -1 && $this->offSet === 0 ){
      return null;
    }

    if( Database::driverIsSqlServer() ){
      return "OffSet {$this->offSet} Rows Fetch Next {$this->limit} Rows Only";
    } else return "Limit {$this->offSet} OffSet {$this->limit}";
  }

  public function paged(
    int $limit,
    int $offset
  ): Repository {
    $this->offSet = $offset;
    $this->limit = ( $limit - 1 ) * $offset;
    return $this;
  }

  public function joins(
  ): array {
    return $this->joins;
  }
  
  public function query(
  ): string {
    return sprintf( "Select %s From Document Where %s %s", 
      $this->selectResult->script,
      $this->whereResult->script,
      $this->definePaged()
    );
  }

  public function fetch(
  ): array {
    return Database::query(
      $this->query(), $this->whereResult->params
    );
  }

  public function row(
  ): mixed {
    return [];
  }
  
  public function rows(
  ): RepositoryResult {
    return new RepositoryResult(
      $this->query(), 
      $this->joins(),
      $this->fetch()
    );
  }
}