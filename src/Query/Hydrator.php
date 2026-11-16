<?php

namespace Websyspro\ArrowToSql\Query;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use stdClass;
use Websyspro\ArrowToSql\Enums\JoinType;
use Websyspro\ArrowToSql\Expressions\ExpJoin;
use Websyspro\ArrowToSql\Query\Clauses\JoinClause;
use Websyspro\Entity\Decorators\EntityList;
use Websyspro\Entity\Schemas\AbstractEntityStructure;

/**
 * Converte as linhas do banco em entidades (stdClass) com seus includes.
 */
class Hydrator
{
  public function __construct(
    private Closure $structureOf,
    private AbstractEntityStructure $rootStructure,
    private JoinClause $joins
  ){}

  public function hydrate(
    array $rows
  ): array {
    if( empty( $this->joins->relateds() ) ){
      $joinDetail = $this->joins->base();

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
      $rows, $this->joins->relateds()[0], $pkIndex
    );
  }

  private function castValue(
    string $typeName,
    mixed $value
  ): mixed {
    if( $value === null ){
      return null;
    }

    if( in_array( $typeName, $this->rootStructure->numericColumnsType(), true ) ){
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

    foreach( $this->joins->linears() as $joinDetail ){
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
    $typeName = $propType instanceof ReflectionNamedType
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
    $entityStructure = ($this->structureOf)( $entityClass );
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
      $childAlias = $childExpJoin->joinDetail->alias;
      $childPk = $childExpJoin->joinDetail->aliasKey;
      $childCol = $childExpJoin->joinDetail->column;
      $childType = $childExpJoin->joinDetail->type;

      // usa a PK real da entidade filho para detecção de duplicata
      // aliasKey é a chave de junção (ex: contatoId), não necessariamente a PK da entidade
      $childRealPk = $childExpJoin->joinDetail->entityClass
        ? ( ($this->structureOf)( $childExpJoin->joinDetail->entityClass )->getPrimaryKey() ?? $childPk )
        : $childPk;
      $childPkVal   = $row[ "{$childAlias}_{$childRealPk}" ]
        ?? $row[ "{$childAlias}_" . strtolower( $childRealPk ) ]
        ?? null;

      // inicializa o campo na entidade pai antes de verificar se há registro filho
      // isso garante que campos sem correspondência (LEFT JOIN) apareçam como null ou []
      if( $isObj ){
        if( isset( $entity->{ $childCol } ) === false ){
          if( $childType === JoinType::List->name ){
            $entity->{ $childCol } = new EntityList();
          } else {
            $entity->{ $childCol } = null;
          }
        }
      } else {
        if( isset( $entity[ $childCol ] ) === false ){
          $entity[ $childCol ] = $childType === JoinType::List->name ? new EntityList() : null;
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
    $result = [];
    $alias = $expJoin->joinDetail->alias;
    $primaryKey = $expJoin->joinDetail->aliasKey;

    if( isset( $pkIndex[ $alias ] ) === false ){
      $pkIndex[ $alias ] = [];
    }

    foreach( $rows as $rawRow ){
      $row  = (array)$rawRow;
      $pkValue = $row[
        "{$alias}_{$primaryKey}"
      ] ?? null;

      if( $pkValue === null ){
        $prefix = "{$alias}_";
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
}
