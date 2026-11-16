<?php

namespace Websyspro\ArrowToSql\Query;

use Websyspro\ArrowToSql\Query\Clauses\GroupByClause;
use Websyspro\ArrowToSql\Query\Clauses\JoinClause;
use Websyspro\ArrowToSql\Query\Clauses\OrderByClause;
use Websyspro\ArrowToSql\Query\Clauses\PagingClause;
use Websyspro\ArrowToSql\Query\Clauses\SelectClause;
use Websyspro\ArrowToSql\Query\Clauses\WhereClause;
use Websyspro\Entity\Schemas\AbstractEntityStructure;

/**
 * Monta o SQL final e a lista de parâmetros a partir das cláusulas.
 * Ordem de montagem = ordem dos placeholders (?) no SQL.
 */
class QueryBuilder
{
  public readonly SelectClause $select;
  public readonly WhereClause $where;
  public readonly GroupByClause $groupBy;
  public readonly OrderByClause $orderBy;
  public readonly PagingClause $paging;
  public readonly JoinClause $joins;

  public function __construct(
    string $entityClass,
    private AbstractEntityStructure $structure
  ){
    $this->select  = new SelectClause( $structure );
    $this->where   = new WhereClause();
    $this->groupBy = new GroupByClause();
    $this->orderBy = new OrderByClause();
    $this->paging  = new PagingClause();
    $this->joins   = new JoinClause( $entityClass, $structure );
  }

  public function toSql(
  ): string {
    $alias = $this->structure->getEntityAlias();

    return sprintf(
      "Select %s From ( Select %s From %s %s %s %s %s ) As %s %s",
        $this->selectedAllsFragment(),
        $this->select->subQueryFragment(),
        $alias,
        $this->where->fragment(),
        $this->groupBy->fragment(),
        $this->orderBy->subQueryFragment( $this->paging->isActive() ),
        $this->paging->fragment( $this->orderBy->hasItems() ),
        $alias,
        $this->joins->fragment()
    );
  }

  /** Ordem dos parâmetros: where principal e depois os joins. */
  public function params(
  ): array {
    return ( new ParamBag() )
      ->merge( $this->where->params() )
      ->merge( $this->joins->params() )
      ->all();
  }

  private function selectedAllsFragment(
  ): string {
    return implode( ", ", [
      ...$this->select->columns( 1 ),
      ...$this->joins->selectedColumns()
    ]);
  }
}
