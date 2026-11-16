<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Websyspro\ArrowToSql\Interfaces\ColumnResult;

/**
 * Cláusula Group By.
 */
class GroupByClause
{
  private ?ColumnResult $result = null;

  public function set(
    ColumnResult $result
  ): void {
    $this->result = $result;
  }

  public function fragment(
  ): string|null {
    if( $this->result === null ){
      return null;
    }

    return sprintf(
      "Group By %s", implode( ",", $this->result->script )
    );
  }
}
