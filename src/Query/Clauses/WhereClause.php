<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Closure;
use Websyspro\ArrowToSql\ArrowToSql;
use Websyspro\ArrowToSql\Enums\ColumnResultType;
use Websyspro\ArrowToSql\Interfaces\RepositoryInternal;
use Websyspro\ArrowToSql\Interfaces\WhereResult;

/**
 * Cláusula Where.
 * É a única fonte da regra de compilação de um filtro: o Where principal
 * e o Where dentro de Include passam por compile().
 */
class WhereClause
{
  private ?WhereResult $result = null;

  public static function compile(
    Closure $closure,
    RepositoryInternal $repository
  ): WhereResult {
    return ( new ArrowToSql( $closure, $repository ))
      ->getWhereResult( ColumnResultType::Where );
  }

  public function set(
    WhereResult $result
  ): void {
    $this->result = $result;
  }

  public function fragment(
  ): string|null {
    return $this->result !== null
      ? "Where {$this->result->script}"
      : null;
  }

  public function params(
  ): array {
    return $this->result !== null
      ? $this->result->params
      : [];
  }
}
