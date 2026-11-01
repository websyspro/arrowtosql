<?php

namespace Websyspro\ArrowToSql;

use ReflectionClass;
use Websyspro\ArrowToSql\Enums\JoinType;
use Websyspro\ArrowToSql\Expressions\ExpJoin;
use Websyspro\ArrowToSql\Interfaces\AppendResultList;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\JoinDetail;
use Websyspro\ArrowToSql\Interfaces\RepositoryResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Decorators\EntityList;
use Websyspro\Entity\Schemas\AbstractEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Connection\Database;
use function is_object;
use function is_array;
use function sprintf;
use function strlen;
use function count;
use Exception;
use Closure;

class Repository
{
  private int $offSet = 0;
  private int $limit = -1;
  public array $includesLinears = [];
  public array $includesRelateds = [];
  private static array $cacheES = [];
  public WhereResult $whereResult;
  public ColumnResult $selectResult;
  public ColumnResult $groupByResult;
  public ColumnResult $orderByResult;
  public ColumnResult $orderByAscResult;
  public ColumnResult $orderByDescResult;
  public AppendResultList $appendResultLists;
  public AbstractEntityStructure $entityStructure;

  public function __construct(
    private readonly string $entity
  ){}

  private function getEntityStructureMatch(
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

  private function arrowToSql(
    Closure|array $closure
  ): ArrowToSql {
    return new ArrowToSQL(
      $closure, $this
    );
  }

  private function joinDetailBase(
  ): JoinDetail {
    return new JoinDetail(
      JoinType::Object->name, null,
      $this->defineAliasToString(),
      $this->definePrimaryKey(),
      null, null, [],
      $this->entity
    );
  }  

  public function registerIncludeLinear(
    JoinDetail $joinDetail
  ): JoinDetail {
    if( empty( $this->includesLinears ) === true ){
      $this->includesLinears[] = $this->joinDetailBase();
    }

    $this->includesLinears[] = $joinDetail;
    return $joinDetail;
  }  

  private function joinDetailIncludes(
    array $closures
  ): array {
    return array_map(
      fn( Closure $closure ) => 
        $this->arrowToSql( $closure )
          ->getIncludesResult(), $closures
    );
  }  

  public function include(
    mixed ...$closures
  ): Repository {
    $newIncludes = $this->joinDetailIncludes( $closures );

    if( empty( $this->includesRelateds ) ){
      $this->includesRelateds = [
        new ExpJoin(
          $this->joinDetailBase(),
          $newIncludes
        )
      ];
    } else {
      $this->includesRelateds[0] = new ExpJoin(
        $this->includesRelateds[0]->joinDetail,
        array_merge( $this->includesRelateds[0]->includes, $newIncludes )
      );
    }

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

  private function defineSelectedAllsWithIncludes(
    array $selectedsWithIncludes = []
  ): string {
    $selectedsWithIncludes = $this->selectResult->script;
    if( empty( $this->includesLinears ) === false ){
      foreach( $this->includesLinears as $includes ){
        if( empty( $includes->columnsSelecteds ) === false ){
          $selectedsWithIncludes = [ 
            ...$selectedsWithIncludes,
            ...$includes->columnsSelecteds
          ];
        }
      }
    }

    return implode(
      ", ", $selectedsWithIncludes
    );
  }

  private function defineSelectedAlls(
  ): string|null {
    return $this->defineSelectedAllsWithIncludes();
  }

  private function defineSelectedFromAliasAll(
  ): string|null {
    return implode(
      ", ", array_map(
      fn( string $columnName ) => preg_replace(
          "#\s.*_#", " ", $columnName
        ), $this->selectResult->script
      )
    );
  }  

  private function defineIncludesToString(
  ): string|null {
    return implode( " ", array_map(
      fn( JoinDetail $expJoin ) => 
        $expJoin->getScript(), 
      $this->includesLinears
    ));
  }

  private function defineWheres(
  ): string|null {
    if( isset( $this->whereResult )){
      return "Where {$this->whereResult->script}";
    }

    return null;
  }

  private function defineParamsToArray(
  ): array {
    if( isset( $this->whereResult )){
      return $this->whereResult->params;
    }
  
    return [];
  }  

  private function definePageds(
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

  public function includesLinears(
  ): array {
    return $this->includesLinears;
  }

  public function includesRelateds(
  ): array {
    return $this->includesRelateds;
  }  

  private function defineScriptBase(
  ): string {
    return "Select %s From ( Select %s From %s %s %s ) As %s %s";
  }
  
  public function defineQueryToString(
  ): string {
    return sprintf(
      $this->defineScriptBase(),
      $this->defineSelectedAlls(),
      $this->defineSelectedFromAliasAll(),
      $this->defineAliasToString(),
      $this->defineWheres(),
      $this->definePageds(),
      $this->defineAliasToString(),
      $this->defineIncludesToString(),
    );
  }

  private function getJoinDetailFromLinear(
    string $alias
  ): JoinDetail|null {
    foreach( $this->includesLinears as $joinDetail ){
      if( $joinDetail->alias === $alias ){
        return $joinDetail;
      }
    }
    return null;
  }

  private function getAliasFromColumnName(
    string $columnName
  ): string|null {
    foreach( $this->includesLinears as $joinDetail ){
      $prefix = $joinDetail->alias . "_";
      if( str_starts_with( $columnName, $prefix ) ){
        return $joinDetail->alias;
      }
    }
    return null;
  }

  private function castValue(
    string $typeName,
    mixed $value
  ): mixed {
    if( $value === null ){
      return null;
    }

    if( in_array( $typeName, $this->getEntityStructure()->numericColumnsType(), true ) ){
      return str_contains( (string) $value, "." )
        ? (float) $value
        : (int) $value;
    }

    return $value;
  }

  private function makeEntity(
    string $alias,
    array $fields,
    ExpJoin|null $expJoin = null
  ): mixed {
    // resolve entityClass do JoinDetail correspondente
    $entityClass = null;

    if( $expJoin !== null ){
      $entityClass = $expJoin->joinDetail->entityClass;
    } else {
      foreach( $this->includesLinears as $joinDetail ){
        if( $joinDetail->alias === $alias ){
          $entityClass = $joinDetail->entityClass;
          break;
        }
      }
    }

    if( $entityClass === null ){
      return $fields;
    }

    $object     = new $entityClass();
    $reflection = new ReflectionClass( $entityClass );

    foreach( $fields as $field => $value ){
      if( $reflection->hasProperty( $field ) === false ){
        $object->{ $field } = $value;
        continue;
      }

      $propType = $reflection->getProperty( $field )->getType();
      $typeName = $propType instanceof \ReflectionNamedType
        ? $propType->getName()
        : null;

      if( $typeName && class_exists( $typeName ) ){
        $typeInstance = new $typeName();
        if( method_exists( $typeInstance, "setValue" ) ){
          $typeInstance->setValue( $this->castValue( $typeName, $value ) );
          $object->{ $field } = $typeInstance;
        } else {
          $object->{ $field } = $value;
        }
      } else {
        $object->{ $field } = $value;
      }
    }

    return $object;
  }

  private function extractFields(
    array $row,
    string $alias,
    string $primaryKey
  ): array {
    $fields = [];
    $prefix = $alias . "_";

    foreach( $row as $columnName => $columnValue ){
      if( str_starts_with( $columnName, $prefix ) ){
        $field = substr( $columnName, strlen( $prefix ) );
        $fields[ $field ] = $columnValue;
      }
    }

    if( isset( $fields[ "id" ] ) ){
      $fields = [ "id" => $fields[ "id" ] ] + $fields;
    }

    return $fields;
  }

  private function buildEntityIncludes(
    array $row,
    ExpJoin $expJoin,
    mixed &$entity,
    bool $isObj
  ): void {
    foreach( $expJoin->includes as $childExpJoin ){
      $childAlias   = $childExpJoin->joinDetail->alias;
      $childPk      = $childExpJoin->joinDetail->aliasKey;
      $childCol     = $childExpJoin->joinDetail->column;
      $childType    = $childExpJoin->joinDetail->type;

      // usa "id" como PK real para detecção de duplicata
      // aliasKey é a chave de junção (ex: documentId), não a PK da entidade
      $childRealPk  = "id";
      $childPkVal   = $row[ "{$childAlias}_{$childRealPk}" ] ?? null;

      if( $childPkVal === null ) continue;

      if( $isObj ){
        if( isset( $entity->{ $childCol } ) === false ){
          if( $childType === JoinType::List->name ){
            $entityList = new EntityList();
            $entityList->setValue( [] );
            $entity->{ $childCol } = $entityList;
          }
        }
      } else {
        if( isset( $entity[ $childCol ] ) === false ){
          $entity[ $childCol ] = $childType === JoinType::List->name ? [] : null;
        }
      }

      if( $childType === JoinType::List->name ){
        $childList = $isObj
          ? $entity->{ $childCol }->getValue()
          : $entity[ $childCol ];

        $childExists = false;
        foreach( $childList as $childItem ){
          $childItemPkVal = is_object( $childItem )
            ? ( $childItem->{ $childRealPk }?->getValue() ?? $childItem->{ $childRealPk } ?? null )
            : ( $childItem[ $childRealPk ] ?? null );

          if( $childItemPkVal === $childPkVal ){
            $childExists = true;
            break;
          }
        }

        if( $childExists === false ){
          $childFields = $this->extractFields( $row, $childAlias, $childPk );
          $childEntity = $this->makeEntity( $childAlias, $childFields, $childExpJoin );
          $childIsObj  = is_object( $childEntity );

          $this->buildEntityIncludes( 
            $row, $childExpJoin, $childEntity, $childIsObj
          );

          if( $isObj ){
            $updatedList   = $entity->{ $childCol }->getValue();
            $updatedList[] = $childEntity;
            $entity->{ $childCol }->setValue( $updatedList );
          } else {
            $entity[ $childCol ][] = $childEntity;
          }
        }
      } else {
        $alreadySet = $isObj 
          ? isset( $entity->{ $childCol } ) 
          : isset( $entity[ $childCol ] );

        if( $alreadySet === false ){
          $childFields = $this->extractFields( $row, $childAlias, $childPk );
          $childEntity = $this->makeEntity( $childAlias, $childFields, $childExpJoin );
          $childIsObj  = is_object( $childEntity );

          $this->buildEntityIncludes( $row, $childExpJoin, $childEntity, $childIsObj );

          if( $isObj ){
            $entity->{ $childCol } = $childEntity;
          } else {
            $entity[ $childCol ] = $childEntity;
          }
        } else {
          // objeto já existe mas pode ter includes filhos (ex: List) ainda não processados
          $childEntity = $isObj
            ? $entity->{ $childCol }
            : $entity[ $childCol ];
          $childIsObj = is_object( $childEntity );

          $this->buildEntityIncludes( $row, $childExpJoin, $childEntity, $childIsObj );

          if( $isObj ){
            $entity->{ $childCol } = $childEntity;
          } else {
            $entity[ $childCol ] = $childEntity;
          }
        }
      }
    }
  }

  private function buildStructure(
    array $rows,
    ExpJoin $expJoin,
    array &$pkIndex = []
  ): array {
    $result     = [];
    $alias      = $expJoin->joinDetail->alias;
    $primaryKey = $expJoin->joinDetail->aliasKey;

    if( isset( $pkIndex[ $alias ] ) === false ){
      $pkIndex[ $alias ] = [];
    }

    foreach( $rows as $rawRow ){
      $row     = (array) $rawRow;
      $pkValue = $row[ $alias . "_" . $primaryKey ] ?? null;

      if( $pkValue === null ) continue;

      if( isset( $pkIndex[ $alias ][ $pkValue ] ) === false ){
        $pkIndex[ $alias ][ $pkValue ] = count( $result );
        $result[] = $this->makeEntity(
          $alias, $this->extractFields( $row, $alias, $primaryKey ), $expJoin
        );
      }

      $index    = $pkIndex[ $alias ][ $pkValue ];
      $isObj    = is_object( $result[ $index ] );

      $this->buildEntityIncludes( $row, $expJoin, $result[ $index ], $isObj );
    }

    return $result;
  }

  private function defineStructureResultRows(
    array $rows
  ): array {
    if( empty( $this->includesRelateds ) ){
      $joinDetail = $this->joinDetailBase();
      $alias      = $joinDetail->alias;
      $primaryKey = $joinDetail->aliasKey;

      return array_map(
        fn( $rawRow ) => $this->makeEntity(
          $alias,
          $this->extractFields( (array) $rawRow, $alias, $primaryKey ),
          new ExpJoin( $joinDetail )
        ),
        $rows
      );
    }

    $pkIndex = [];
    return $this->buildStructure( 
      $rows, $this->includesRelateds[0], $pkIndex
    );
  }

  public function defineFetchToRows(
    array $rows = []
  ): array {
    $rows = Database::query(
      $this->defineQueryToString(), 
      $this->defineParamsToArray()
    );

    return $this->defineStructureResultRows( $rows );
  }

  public function row(
  ): mixed {
    return [];
  }
  
  public function rows(
  ): array {
    $result = $this->defineFetchToRows();
    return $result;
  }
}