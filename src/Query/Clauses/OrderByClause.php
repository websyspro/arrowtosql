<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Websyspro\ArrowToSql\Enums\OrderByType;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\Connection\Database;

/**
 * Cláusula Order By.
 */
class OrderByClause
{
  private array $items = [];

  public function add(
    OrderByType $orderByType,
    ColumnResult $columnResult
  ): void {
    foreach( $columnResult->script as $script ){
      $this->items[] = "{$script} {$orderByType->name}";
    }
  }

  public function hasItems(
  ): bool {
    return empty( $this->items ) === false;
  }

  public function fragment(
  ): string|null {
    if( $this->hasItems() === false ){
      return null;
    }

    return sprintf( "Order By %s", implode( ",", $this->items ));
  }

  /**
   * Order By dentro da subquery. No SQL Server exige OFFSET/FETCH
   * para existir ORDER BY dentro de subquery, por isso o "Order By 1"
   * quando há paginação sem ordenação explícita.
   */
  public function subQueryFragment(
    bool $pagingIsActive
  ): string|null {
    if( Database::driverIsSqlServer() ){
      if( $this->hasItems() ){
        return $this->fragment();
      }

      return $pagingIsActive ? "Order By 1" : null;
    }

    return $this->fragment();
  }
}
