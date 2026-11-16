<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Websyspro\Connection\Database;

/**
 * Cláusula de paginação (offset/limit) específica por banco.
 */
class PagingClause
{
  private int $offSet = 0;
  private int $limit = -1;

  public function paged(
    int $page,
    int $pageSize
  ): void {
    $this->offSet = ( $page - 1 ) * $pageSize;
    $this->limit  = $pageSize;
  }

  public function isActive(
  ): bool {
    return $this->limit !== -1 || $this->offSet !== 0;
  }

  public function fragment(
    bool $hasOrderBy
  ): string|null {
    if( Database::driverIsSqlServer() ){
      // Sem paginação mas com ORDER BY: usa OFFSET 0 ROWS (sem FETCH NEXT)
      // para permitir ORDER BY dentro da subquery, que é inválido sem OFFSET/FETCH.
      if( $this->limit === -1 && $this->offSet === 0 ){
        return $hasOrderBy ? "OffSet 0 Rows" : null;
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
}
