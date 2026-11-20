<?php

namespace Websyspro\ArrowToSql;

use Closure;

class ExpressionInclude
{
  public ExpressionWhere $expressionWhere;
  public array $expressionSelect = [];
  public array $expressionIncludes = [];

  public function __construct(
    private Parser $parser,
    private Repository $repository
  ){
    $this->createExpressionWhere();
  }

  private function createExpressionWhere(
  ): void {
    $this->expressionWhere = ExpressionIncludeUtils::extractScopeWhere( $this->parser->tokens );
  }
}