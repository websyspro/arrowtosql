<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Expressions\AbstractExpType;
use Websyspro\Utils\Collection;

class SelectBuilder
{
  public Collection $builder;

  public function __construct(
    private ExpressionSelect $expressionSelect
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
    foreach( $this->expressionSelect->parserScript as $expObject ){
      if( $expObject instanceof AbstractExpType ){
        $this->builder->add( $expObject->get() );
      }
    }
    
    return $this->builder->joinWithSpace();
  }

  public static function buildSql(
    ExpressionSelect $expressionSelect
  ): SelectBuilder {
    return new static( $expressionSelect );
  }  
}
