<?php

namespace Websyspro\ArrowToSql\Query\Clauses;

use Websyspro\ArrowToSql\Enums\JoinType;
use Websyspro\ArrowToSql\Expressions\ExpJoin;
use Websyspro\ArrowToSql\Interfaces\JoinDetail;
use Websyspro\Entity\Schemas\AbstractEntityStructure;

/**
 * Cláusula de Include (Left Join).
 * - linears: joins resolvidos em ordem, usados para montar o SQL e o select.
 * - relateds: árvore de includes usada para hidratar o resultado.
 */
class JoinClause
{
  private array $linears = [];
  private array $relateds = [];

  public function __construct(
    private string $entityClass,
    private AbstractEntityStructure $structure
  ){}

  /** JoinDetail da entidade principal, usado como raiz da hidratação. */
  public function base(
  ): JoinDetail {
    return new JoinDetail(
      JoinType::Object->name, null,
      $this->structure->getEntityAlias(),
      $this->structure->getPrimaryKey(),
      null, null, [],
      $this->entityClass
    );
  }

  /** Registra um join resolvido pelo ArrowToSql. */
  public function registerLinear(
    JoinDetail $joinDetail
  ): JoinDetail {
    if( empty( $this->linears ) === true ){
      $this->linears[] = $this->base();
    }

    $this->linears[] = $joinDetail;
    return $joinDetail;
  }

  /** Acrescenta includes de primeiro nível à árvore de hidratação. */
  public function addRelated(
    array $includes
  ): void {
    if( empty( $this->relateds ) ){
      $this->relateds = [
        new ExpJoin( $this->base(), $includes )
      ];
    } else {
      $this->relateds[0] = new ExpJoin(
        $this->relateds[0]->joinDetail,
        array_merge( $this->relateds[0]->includes, $includes )
      );
    }
  }

  public function linears(
  ): array {
    return $this->linears;
  }

  public function relateds(
  ): array {
    return $this->relateds;
  }

  public function fragment(
  ): string {
    return implode( " ", array_map(
      fn( JoinDetail $joinDetail ) => $joinDetail->getScript(),
        $this->linears
    ));
  }

  public function params(
  ): array {
    $params = [];

    foreach( $this->linears as $joinDetail ){
      if( $joinDetail instanceof JoinDetail && $joinDetail->joinWhere !== null ){
        $params = array_merge( $params, $joinDetail->joinWhere->params );
      }
    }

    return $params;
  }

  /** Colunas extras dos includes, acrescentadas ao select principal. */
  public function selectedColumns(
  ): array {
    $columns = [];

    foreach( $this->linears as $joinDetail ){
      if( empty( $joinDetail->columnsSelecteds ) === false ){
        $columns = [ ...$columns, ...$joinDetail->columnsSelecteds ];
      }
    }

    return $columns;
  }
}
