<?php

namespace Websyspro\ArrowToSql;

use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\AbstractEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use ReflectionClass;
use Closure;

class Repository
{
  protected static array $cacheES = [];
  protected array $expressionIncludes = [];
  protected ExpressionRelationship $expressionRelationship;
  protected ExpressionWhere $expressionWhere;
  protected ExpressionSelect $expressionSelect;
  public SelectBuilder $selectBuilder;
  public WhereBuilder $whereBuilder;
  
  public function __construct(
    protected string $entity
  ){}

  public function getEntityStructureMatch(
    ReflectionClass $reflectionClass
  ): AbstractEntityStructure {
    return match( Database::driver()){
      DriverType::SqlServer => new SqlServerEntityStructure( $reflectionClass ),
      DriverType::Postgres => new PostgresEntityStructure( $reflectionClass ),
      DriverType::SqLite => new SqLiteEntityStructure( $reflectionClass ),
      DriverType::MySql => new MySqlEntityStructure( $reflectionClass ),
    };   
  } 

  public function getEntityStructure(
  ): AbstractEntityStructure {
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

  public function getEntityStructureByString(
    string $entity
  ): AbstractEntityStructure {
    if( isset( static::$cacheES[ $entity ])){
      return static::$cacheES[ $entity ];
    }

    $reflectionClass = new ReflectionClass( $entity );
    static::$cacheES[ $entity ] = $this->getEntityStructureMatch( $reflectionClass );
    return static::$cacheES[ $entity ];
  }  

  private function defineAliasToString(
  ): string {
    return $this->getEntityStructure()->getEntityAlias();
  }

  private function definePrimaryKey(
  ): string {
    return $this->getEntityStructure()->getPrimaryKey();
  }
  
  public function include(
    mixed ...$closuresOfIncludes
  ): Repository {
    $this->expressionIncludes = ParserUtils::mapperStatic(
      $closuresOfIncludes, fn( Closure $closure ) => (
        ParserUtils::createRepositoryInRuntimeFromIncludes(
          new Parser($closure, [], []), $this
        )
      )
    );

    return $this;
  }

  public function where(
    Closure $closure
  ): Repository {
    $this->expressionWhere = new ExpressionWhere(
      new Parser($closure, [], []), $this
    );

    return $this;
  }

  public function select(
    Closure $closure
  ): Repository {
    $this->expressionSelect = new ExpressionSelect(
      new Parser($closure, [], []), $this
    );

    return $this;
  }

  public function buildSql(
  ): Repository {
    $this->selectBuilder = SelectBuilder::buildSql( $this->expressionSelect );
    $this->whereBuilder = WhereBuilder::buildSql( $this->expressionWhere );
    return $this;
  }
  
  public function rows(
  ): mixed {
    return $this;
  }
}