<?php

namespace Websyspro\ArrowToSql;

use Closure;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;

class RepositoryInRuntime
extends Repository
{
  public function __construct(
    public string $entity,
    public array $uses,
    public array $scopes,
    public Closure $closure
  ){
    parent::__construct( $entity );
  }

  public function indexOf(
    array $tokens,
    int $type,
    string $value
  ): int {
    foreach( $tokens as $cursor => $token ){
      if( $token->key === $type && $token->value === $value ){
        return $cursor;
      }
    }

    return -1;
  }  

  public function setWheres(
    array $tokens = []
  ): RepositoryInRuntime {


    $this->expressionWhere = new ExpressionWhere(
      new Parser( $tokens, $this->uses, $this->scopes, $this->closure ), $this
    );    

    return $this;
  }

  public function setSelect(
    array $tokens = []
  ): RepositoryInRuntime {
    $this->expressionSelect = new ExpressionSelect(
      new Parser( $tokens, $this->uses, $this->scopes, $this->closure ), $this
    );    

    return $this;
  }  
}