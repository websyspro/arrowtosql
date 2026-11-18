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

  public function instance(
    array $tokens = [], 
    array $parentUses = [],
    array $parentScopes = [],
    Closure|null $parentClosure = null
  ): Repository {
    $parserWhere = new Parser( RepositoryInRuntime::extractTokensFromString( "where", $tokens ), $parentUses, $parentScopes, $parentClosure );
    // $parserSelect = new Parser( RepositoryInRuntime::extractTokensFromString( "select", $tokens ), $parentUses, $parentScopes, $parentClosure );
    
    return Repository::inRuntime( 
      array_values( $parserWhere->scopes )[0], $parserWhere//, $parserSelect
    );
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

  public function extractTokensByStr(
    string $value,
    array $tokens = []
  ): array {
    $tokens = ParserUtils::sliceStatic(
      $tokens, RepositoryInRuntime::indexOf(
        $tokens, T_STRING, $value
      ) + 3
    );

    return Parser::contextsNotEndsStatic( $tokens );
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