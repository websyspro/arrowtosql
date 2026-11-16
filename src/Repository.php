<?php

namespace Websyspro\ArrowToSql;

use ReflectionNamedType;
use stdClass;
use Websyspro\ArrowToSql\Enums\ColumnResultType;
use Websyspro\ArrowToSql\Enums\OrderByType;
use Websyspro\ArrowToSql\Interfaces\AppendResultList;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\JoinDetail;
use Websyspro\ArrowToSql\Query\Clauses\WhereClause;
use Websyspro\ArrowToSql\Query\Hydrator;
use Websyspro\ArrowToSql\Query\QueryBuilder;
use Websyspro\Entity\Decorators\EntityList;
use Websyspro\Entity\Schemas\AbstractEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Connection\Database;
use Websyspro\ArrowToSql\Interfaces\RepositoryInternal;
use Websyspro\ArrowToSql\Interfaces\RepositoryPublic;
use Websyspro\Entity\Types\ColumnAbstract;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Utils\Collection;
use ReflectionClass;
use Throwable;
use Closure;

/**
 * Fachada pública do ArrowToSql.
 * Recebe as chamadas (where, select, include, ...), compila as closures
 * com ArrowToSql e delega o estado às cláusulas do QueryBuilder.
 * A montagem do SQL fica no QueryBuilder e a hidratação no Hydrator.
 */
class Repository
implements RepositoryPublic, RepositoryInternal
{
  private static array $cacheES = [];
  private ?QueryBuilder $query = null;
  private AppendResultList $appendResultLists;

  public function __construct(
    private readonly string $entity
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

    $reflectionClass = new ReflectionClass( $this->entity );
    static::$cacheES[ $this->entity ] = $this->getEntityStructureMatch( $reflectionClass );

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

  /** Estado da consulta (cláusulas), criado na primeira utilização. */
  private function query(
  ): QueryBuilder {
    return $this->query ??= new QueryBuilder(
      $this->entity, $this->getEntityStructure()
    );
  }

  private function arrowToSql(
    Closure|array $closure
  ): ArrowToSql {
    return new ArrowToSql(
      $closure, $this
    );
  }

  public function registerIncludeLinear(
    JoinDetail $joinDetail
  ): JoinDetail {
    return $this->query()->joins->registerLinear( $joinDetail );
  }

  private function joinDetailIncludes(
    array $closures
  ): array {
    return array_map(
      fn( Closure|JoinArrowToSql $closure ) =>
        $this->arrowToSql(
          $closure instanceof JoinArrowToSql ? $closure->closure : $closure
        )->getIncludesResult(
          $closure instanceof JoinArrowToSql ? $closure->where : null
        ), $closures
    );
  }

  private function fetchToRows(
  ): array {
    $rows = Database::query(
      $this->query()->toSql(),
      $this->query()->params()
    );

    return ( new Hydrator(
      $this->getEntityStructureByString( ... ),
      $this->getEntityStructure(),
      $this->query()->joins
    ))->hydrate( $rows );
  }

  public function include(
    mixed ...$closures
  ): static {
    $this->query()->joins->addRelated(
      $this->joinDetailIncludes( $closures )
    );

    return $this;
  }

  public function where(
    Closure $closure
  ): static {
    $this->query()->where->set(
      WhereClause::compile( $closure, $this )
    );

    return $this;
  }

  public function select(
    Closure $closure
  ): static {
    $this->query()->select->set(
      $this->arrowToSql( $closure )->getColumnResult( ColumnResultType::Select )
    );

    return $this;
  }

  public function groupBy(
    Closure $closure
  ): static {
    $this->query()->groupBy->set(
      $this->arrowToSql( $closure )->getColumnResult( ColumnResultType::GroupBy )
    );

    return $this;
  }

  public function orderBy(
    Closure $closure
  ): static {
    return $this->orderByWith( OrderByType::Asc, $closure );
  }

  public function orderByAsc(
    Closure $closure
  ): static {
    return $this->orderByWith( OrderByType::Asc, $closure );
  }

  public function orderByDesc(
    Closure $closure
  ): static {
    return $this->orderByWith( OrderByType::Desc, $closure );
  }

  private function orderByWith(
    OrderByType $orderByType,
    Closure $closure
  ): static {
    $columnResult = $this->arrowToSql( $closure )
      ->getColumnResult( ColumnResultType::OrderBy );

    $this->query()->orderBy->add( $orderByType, $columnResult );
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
    } catch( \Exception $exception ){}
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

  public function paged(
    int $page,
    int $pageSize
  ): static {
    $this->query()->paging->paged( $page, $pageSize );
    return $this;
  }

  private function castToModel(
    string $modelClass,
    stdClass|array $data
  ): mixed {
    if( class_exists( $modelClass ) === false ){
      return $data;
    }

    $model = new $modelClass();
    $reflection = new ReflectionClass( $modelClass );
    $source = is_array( $data ) ? (object) $data : $data;

    foreach( $reflection->getProperties() as $reflProp ){
      if( $reflProp->isInitialized( $model ) ) continue;

      $type = $reflProp->getType();
      $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

      if( $typeName === null || class_exists( $typeName ) === false ) continue;

      try {
        $instance = new $typeName();
        $reflProp->setValue( $model, $instance );
      } catch( Throwable ){}
    }

    foreach( get_object_vars( $source ) as $prop => $value ){
      if( $reflection->hasProperty( $prop ) === false ){
        try {
          $model->{ $prop } = $value;
        } catch( Throwable ){}
        continue;
      }

      $reflProp = $reflection->getProperty( $prop );
      $type = $reflProp->getType();
      $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

      if( $value instanceof EntityList ){
        $entityAttr = $reflProp->getAttributes( EntityList::class );
        $itemClass  = empty( $entityAttr ) === false
          ? $entityAttr[0]->newInstance()->getEntity()
          : null;

        if( $itemClass !== null && class_exists( $itemClass ) ){
          $castedItems = array_map(
            fn( $item ) => $this->castToModel( $itemClass, $item ),
            $value->items
          );
          $model->{ $prop } = new EntityList( $itemClass, $castedItems );
        } else {
          $model->{ $prop } = $value;
        }
        continue;
      }

      if( $value instanceof Collection ){
        $model->{ $prop } = $value;
        continue;
      }

      if( $value instanceof stdClass && $typeName !== null && class_exists( $typeName ) ){
        $model->{ $prop } = $this->castToModel( $typeName, $value );
        continue;
      }

      $currentVal = $reflProp->isInitialized( $model ) ? $reflProp->getValue( $model ) : null;
      if( $currentVal instanceof ColumnAbstract ){
        $currentVal->setValue( $value );
        continue;
      }

      try {
        $model->{ $prop } = $value;
      } catch( Throwable ){}
    }

    return $model;
  }

  public function row(
    string|null $modelClass = null
  ): mixed {
    $rows = $this->fetchToRows();

    if( empty( $rows ) ){
      return null;
    }

    if( $modelClass !== null ){
      return $this->castToModel(
        $modelClass, $rows[
          array_key_first( $rows )
        ]
      );
    }

    return $rows[
      array_key_first( $rows )
    ];
  }

  public function rows(
    string|null $modelClass = null
  ): Collection {
    if( $modelClass !== null ){
      return new Collection(
        array_map( fn( $item ) => $this->castToModel(
          $modelClass, $item
          ), $this->fetchToRows()
        )
      );
    }

    return new Collection(
      $this->fetchToRows()
    );
  }
}
