<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Expressions\AbstractExpType;
use Websyspro\Utils\Collection;

class WhereBuilder
{
  public Collection $builder;

  public function __construct(
    private ExpressionWhere $expressionWhere
  ){
    $this->createBuilder();
    $this->compilerBuilder();
  }

  private function createBuilder(
  ): void {
    $this->builder = new Collection();
  }

  private function compilerBuilder(
  ): string {
    foreach( $this->expressionWhere->parserScript as $expObject ){
      if( $expObject instanceof AbstractExpType ){
        $this->builder->add( $expObject->get() );
      }
    }
    
    return $this->builder->joinWithSpace();
  }

  public static function buildSql(
    ExpressionWhere $expressionWhere
  ): WhereBuilder {
    return new static( $expressionWhere );
  }  
}
