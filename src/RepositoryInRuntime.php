<?php

namespace Websyspro\ArrowToSql;

use Closure;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Interfaces\JoinDetail;

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
  
  public function registerRelationship(
    Repository $repositoryParent
  ): RepositoryInRuntime {
    $this->expressionRelationship = new ExpressionRelationship(
      $this, $repositoryParent
    );

    return $this;
  }  

  public function registerWheres(
    array $tokens = []
  ): RepositoryInRuntime {
    $this->expressionWhere = new ExpressionWhere(
      new Parser( $tokens, $this->uses, $this->scopes, $this->closure ), $this
    );    

    return $this;
  }

  public function registerSelect(
    array $tokens = []
  ): RepositoryInRuntime {
    $this->expressionSelect = new ExpressionSelect(
      new Parser( $tokens, $this->uses, $this->scopes, $this->closure ), $this
    );    

    return $this;
  }
  
  public function registerIncludes(
    array $closuresOfIncludes = []
  ): RepositoryInRuntime {
    $this->expressionIncludes = ParserUtils::mapperStatic(
      $closuresOfIncludes, fn( array $closure ) => (
        ParserUtils::createRepositoryInRuntimeFromIncludes(
          new Parser( $closure, $this->uses, $this->scopes, $this->closure ), $this
        )
      )
    );    

    return $this;
  }
}