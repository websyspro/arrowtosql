<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\Connection\Database;
use Websyspro\Entity\Schemas\AbstractEntityStructure;

/**
 * Cláusula Select das colunas da entidade principal.
 */
class SelectClause
{
  private ?ColumnResult $result = null;

  public function __construct(
    private AbstractEntityStructure $structure
  ){}

  public function set(
    ColumnResult $result
  ): void {
    $this->result = $result;
  }

  /**
   * order 0: colunas no formato "alias.coluna" (subquery interna)
   * order 1: colunas com alias de retorno "alias.coluna As alias_coluna"
   */
  public function columns(
    int $order = 0
  ): array {
    if( $this->result === null ){
      $alias = $this->structure->entityNames->alias;
      $columns = [];

      foreach( $this->structure->columns->items as $column ){
        if( $order === 1 ){
          $columns[] = Database::driverIsPostgres()
            ? "{$alias}.{$column} As \"{$alias}_{$column}\""
            : "{$alias}.{$column} As {$alias}_{$column}";
        } else {
          $columns[] = "{$alias}.{$column}";
        }
      }

      return $columns;
    }

    return array_map(
      fn( array $script ) => $script[ $order ],
        $this->result->script
    );
  }

  public function subQueryFragment(
  ): string {
    return implode( ", ", $this->columns( 0 ));
  }
}
