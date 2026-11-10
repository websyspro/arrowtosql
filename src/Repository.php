<?php

namespace Websyspro\ArrowToSql;

use stdClass;
use Websyspro\ArrowToSql\Enums\ColumnResultType;
use Websyspro\ArrowToSql\Enums\JoinType;
use Websyspro\ArrowToSql\Enums\OrderByType;
use Websyspro\ArrowToSql\Expressions\ExpJoin;
use Websyspro\ArrowToSql\Interfaces\AppendResultList;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\JoinDetail;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\AbstractEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Connection\Database;
use Websyspro\ArrowToSql\Interfaces\RepositoryInternal;
use Websyspro\ArrowToSql\Interfaces\RepositoryPublic;
use Websyspro\Utils\Collection;
use function is_object;
use function is_array;
use function sprintf;
use function strlen;
use function count;
use function in_array;
use ReflectionClass;
use Exception;
use Closure;

class Repository
implements RepositoryPublic, RepositoryInternal
{
  private int $offSet = 0;
  private int $limit = -1;
  private array $includesLinears = [];
  private array $includesRelateds = [];
  private array $orderByResult = [];
  private static array $cacheES = [];
  private WhereResult $whereResult;
  private ColumnResult $selectResult;
  private ColumnResult $groupByResult;
  private AppendResultList $appendResultLists;
  private AbstractEntityStructure $entityStructure;

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
  
  private function registerOrderBy(
    OrderByType $orderByType,
    ColumnResult $columnResult
  ): static {
    foreach( $columnResult->script as $script ){
      $this->orderByResult[] = "{$script} {$orderByType->name}";
    }

    return $this;
  }

  private function defineSelectdColumnsByOrder(
    int $order = 0,
    array $columnsAsterisc = []
  ): array {
    if( isset( $this->selectResult ) === false ){
      foreach( $this->getEntityStructure()->columns->items as $column ){
        $alias = $this->getEntityStructure()->entityNames->alias;

        if( $order === 1 ){
          $columnsAsterisc[] = Database::driverIsPostgres()
            ? "{$alias}.{$column} As \"{$alias}_{$column}\""
            : "{$alias}.{$column} As {$alias}_{$column}";
        } else {
          $columnsAsterisc[] = "{$alias}.{$column}";
        }
      }

      return $columnsAsterisc;
    }

    return array_map(
      fn( array $script ) => $script[ $order ],
        $this->selectResult->script
    );
  }

  private function defineSelectedAllsWithIncludes(
    array $selectedsWithIncludes = []
  ): string {
    
    $selectedsWithIncludes = $this->defineSelectdColumnsByOrder(1);
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

  private function defineSelectedFromSubQueryAll(
  ): string|null {
    return implode(
      ", ", $this->defineSelectdColumnsByOrder(0)
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

  private function defineGroupBys(
  ): string|null {
    if( isset( $this->groupByResult )){
      return sprintf( 
        "Group By %s", implode( ",", $this->groupByResult->script )
      );
    }

    return null;
  }

  private function defineOrderBys(
  ): string|null {
    if( empty( $this->orderByResult ) === false){
      return sprintf( 
        "Order By %s", implode( 
          ",", $this->orderByResult
        )
      );
    }

    return null;
  }

  private function hasActivePaging(
  ): bool {
    return $this->limit !== -1 || $this->offSet !== 0;
  }  

  private function defineOrderBysInSubQuery(
  ): string|null {
    if( Database::driverIsSqlServer() ){
      if( empty( $this->orderByResult ) === false ){
        return $this->defineOrderBys();
      }
      if( $this->hasActivePaging() ){
        return "Order By 1";
      }
      return null;
    }

    return $this->defineOrderBys();
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
    if( Database::driverIsSqlServer() ){
      // Sem paginação mas com ORDER BY: usa OFFSET 0 ROWS (sem FETCH NEXT)
      // para permitir ORDER BY dentro da subquery, que é inválido sem OFFSET/FETCH.
      if( $this->limit === -1 && $this->offSet === 0 ){
        if( empty( $this->orderByResult ) === false ){
          return "OffSet 0 Rows";
        }
        return null;
      }

      return "OffSet {$this->offSet} Rows Fetch Next {$this->limit} Rows Only";
    }

    if( $this->limit === -1 && $this->offSet === 0 ){
      return null;
    }

    if( Database::driverIsPostgres() ){
      return "OffSet {$this->offSet} Rows Fetch Next {$this->limit} Rows Only";
    }

    return "Limit {$this->limit} OffSet {$this->offSet}";
  } 
  
  private function defineScriptBase(
  ): string {
    return "Select %s From ( Select %s From %s %s %s %s %s ) As %s %s";
  }
  
  private function defineQueryToString(
  ): string {
    return sprintf(
      $this->defineScriptBase(),
      $this->defineSelectedAlls(),
      $this->defineSelectedFromSubQueryAll(),
      $this->defineAliasToString(),
      $this->defineWheres(),
      $this->defineGroupBys(),
      $this->defineOrderBysInSubQuery(),
      $this->definePageds(),
      $this->defineAliasToString(),
      $this->defineIncludesToString(),
    );
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

  private function resolveEntityClass(
    string $alias,
    ExpJoin|null $expJoin
  ): string|null {
    if( $expJoin !== null ){
      return $expJoin->joinDetail->entityClass;
    }

    foreach( $this->includesLinears as $joinDetail ){
      if( $joinDetail->alias === $alias ){
        return $joinDetail->entityClass;
      }
    }

    return null;
  }

  private function resolveFieldValue(
    ReflectionClass $reflection,
    string $column,
    mixed $value
  ): mixed {
    if( $reflection->hasProperty( $column ) === false ){
      return $value;
    }

    $propType = $reflection->getProperty( $column )->getType();
    $typeName = $propType instanceof \ReflectionNamedType
      ? $propType->getName()
      : null;

    if( $typeName === null || class_exists( $typeName ) === false ){
      return $value;
    }

    $typeInstance = new $typeName();

    if( method_exists( $typeInstance, "setValue" ) === false ){
      return $value;
    }

    $castedValue   = $this->castValue( $typeName, $value );
    $precisionAttr = $reflection->getProperty( $column )
      ->getAttributes( \Websyspro\Entity\Decorators\Precision::class );

    if( !empty( $precisionAttr ) && is_float( $castedValue ) ){
      $scale       = $precisionAttr[0]->newInstance()->scale;
      $castedValue = round( $castedValue, $scale );
    }

    return $typeInstance->setValue( $castedValue )->getValue();
  }

  private function buildStdClass(
    ReflectionClass $reflection,
    array $orderedColumns,
    array $fields,
    array $activeIncludeColumns,
    array $extraFields
  ): stdClass {
    $object = new stdClass();

    // 1. inicializa na ordem da Entity: campos selecionados + virtuais ativos
    foreach( $orderedColumns as $column ){
      $isInFields = array_key_exists( $column, $fields );
      $isVirtual  = in_array( $column, $activeIncludeColumns, true );

      if( $isInFields || $isVirtual ){
        $object->{ $column } = null;
      }
    }

    // 2. preenche os campos que vieram do banco
    foreach( $orderedColumns as $column ){
      if( array_key_exists( $column, $fields ) === false ) continue;
      $object->{ $column } = $this->resolveFieldValue( $reflection, $column, $fields[ $column ] );
    }

    // 3. campos extras ao final (aliases de aggregate, colunas calculadas, etc.)
    foreach( $extraFields as $field => $value ){
      $object->{ $field } = $value;
    }

    return $object;
  }

  private function makeEntity(
    string $alias,
    array $fields,
    ExpJoin|null $expJoin = null,
    array $activeIncludes = []
  ): stdClass|array {
    $entityClass = $this->resolveEntityClass( $alias, $expJoin );

    if( $entityClass === null ){
      return $fields;
    }

    $reflection      = new ReflectionClass( $entityClass );
    $entityStructure = $this->getEntityStructureByString( $entityClass );
    $orderedColumns  = $entityStructure->columnsAlls->items ?? [];
    $extraFields     = array_diff_key( $fields, array_flip( $orderedColumns ) );

    $activeIncludeColumns = array_map(
      fn( ExpJoin $inc ): string => $inc->joinDetail->column ?? "",
      $activeIncludes
    );

    return $this->buildStdClass(
      $reflection,
      $orderedColumns,
      $fields,
      $activeIncludeColumns,
      $extraFields
    );
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

      // usa a PK real da entidade filho para detecção de duplicata
      // aliasKey é a chave de junção (ex: contatoId), não necessariamente a PK da entidade
      $childRealPk = $childExpJoin->joinDetail->entityClass
        ? ( $this->getEntityStructureByString( $childExpJoin->joinDetail->entityClass )->getPrimaryKey() ?? $childPk )
        : $childPk;
      $childPkVal   = $row[ "{$childAlias}_{$childRealPk}" ]
        ?? $row[ "{$childAlias}_" . strtolower( $childRealPk ) ]
        ?? null;

      // inicializa o campo na entidade pai antes de verificar se há registro filho
      // isso garante que campos sem correspondência (LEFT JOIN) apareçam como null ou []
      if( $isObj ){
        if( isset( $entity->{ $childCol } ) === false ){
          if( $childType === JoinType::List->name ){
            $entity->{ $childCol } = new Collection();
          } else {
            $entity->{ $childCol } = null;
          }
        }
      } else {
        if( isset( $entity[ $childCol ] ) === false ){
          $entity[ $childCol ] = $childType === JoinType::List->name ? new Collection() : null;
        }
      }

      if( $childPkVal === null ) continue;

      if( $childType === JoinType::List->name ){
        $childList = $isObj
          ? $entity->{ $childCol }->items
          : $entity[ $childCol ]->items;

        $childExists = false;
        foreach( $childList as $childItem ){
          $childItemPkVal = is_object( $childItem )
            ? ( $childItem->{ $childRealPk } ?? null )
            : ( $childItem[ $childRealPk ] ?? null );

          if( $childItemPkVal === $childPkVal ){
            $childExists = true;
            break;
          }
        }

        if( $childExists === false ){
          $childFields = $this->extractFields( $row, $childAlias, $childPk );
          $childEntity = $this->makeEntity( $childAlias, $childFields, $childExpJoin, $childExpJoin->includes );
          $childIsObj  = is_object( $childEntity );

          $this->buildEntityIncludes( 
            $row, $childExpJoin, $childEntity, $childIsObj
          );

          if( $isObj ){
            $entity->{ $childCol }->items[] = $childEntity;
          } else {
            $entity[ $childCol ]->items[] = $childEntity;
          }
        }
      } else {
        $alreadySet = $isObj 
          ? isset( $entity->{ $childCol } ) 
          : isset( $entity[ $childCol ] );

        if( $alreadySet === false ){
          $childFields = $this->extractFields( $row, $childAlias, $childPk );
          $childEntity = $this->makeEntity( $childAlias, $childFields, $childExpJoin, $childExpJoin->includes );
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

      // quando a PK não foi selecionada (ex: groupBy/select customizado),
      // usa a primeira coluna disponível com o prefixo do alias como chave
      if( $pkValue === null ){
        $prefix = $alias . "_";
        foreach( $row as $colName => $colValue ){
          if( str_starts_with( $colName, $prefix ) && $colValue !== null ){
            $pkValue = $colValue;
            break;
          }
        }
      }

      if( $pkValue === null ) continue;

      if( isset( $pkIndex[ $alias ][ $pkValue ] ) === false ){
        $pkIndex[ $alias ][ $pkValue ] = count( $result );
        $result[] = $this->makeEntity(
          $alias, $this->extractFields( $row, $alias, $primaryKey ), $expJoin,
          $expJoin->includes
        );
      }

      $index = $pkIndex[ $alias ][ $pkValue ];
      $isObj = is_object( $result[ $index ] );

      $this->buildEntityIncludes( $row, $expJoin, $result[ $index ], $isObj );
    }

    return $result;
  }

  private function defineStructureResultRows(
    array $rows
  ): array {
    if( empty( $this->includesRelateds ) ){
      $joinDetail = $this->joinDetailBase();

      return array_map(
        fn( $rawRow ) => $this->makeEntity(
          $joinDetail->alias,
          $this->extractFields(
            (array)$rawRow, 
            $joinDetail->alias, 
            $joinDetail->aliasKey
          ),
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

  private function defineFetchToRows(
    array $rows = []
  ): array {
    $rows = Database::query(
      $this->defineQueryToString(), 
      $this->defineParamsToArray()
    );

    return $this->defineStructureResultRows( $rows );
  }  

  public function include(
    mixed ...$closures
  ): static {
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
  ): static {
    $this->whereResult = $this->arrowToSql($closure)
      ->getWhereResult( ColumnResultType::Where );
    
    return $this;
  }

  public function select(
    Closure $closure
  ): static {
    $this->selectResult = $this->arrowToSql( $closure )
      ->getColumnResult( ColumnResultType::Select );
    
    return $this;
  }

  public function groupBy(
    Closure $closure
  ): static {
    $this->groupByResult = $this->arrowToSql($closure)
      ->getColumnResult( ColumnResultType::GroupBy );
    
    return $this;
  }

  public function orderBy(
    Closure $closure
  ): static {
    $orderByResult = $this->arrowToSql($closure)
      ->getColumnResult( ColumnResultType::OrderBy );

    return $this->registerOrderBy( OrderByType::Asc, $orderByResult );
  }
  
  public function orderByAsc(
    Closure $closure
  ): static {
    $orderByAscResult = $this->arrowToSql($closure)
      ->getColumnResult( ColumnResultType::OrderBy );

    return $this->registerOrderBy( OrderByType::Asc, $orderByAscResult );
  }

  public function orderByDesc(
    Closure $closure
  ): static {
    $orderByDescResult = $this->arrowToSql($closure)
      ->getColumnResult( ColumnResultType::OrderBy );
      
    return $this->registerOrderBy( OrderByType::Desc, $orderByDescResult );
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

  public function paged(
    int $page,
    int $pageSize
  ): static {
    $this->offSet = ( $page - 1 ) * $pageSize;
    $this->limit = $pageSize;
    return $this;
  }

  // public function includesLinears(
  // ): array {
  //   return $this->includesLinears;
  // }

  // public function includesRelateds(
  // ): array {
  //   return $this->includesRelateds;
  // }  

  public function row(
  ): mixed {
    return [];
  }
  
  public function rows(
  ): Collection {
    return new Collection(
      $this->defineFetchToRows()
    );
  }
}