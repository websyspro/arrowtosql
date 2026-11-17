<?php

namespace Websyspro\ArrowToSql;

use Closure;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;

class RepositoryInRuntime
{
  public static function instance(
    array $tokens = [], 
    array $parentUses = [],
    array $parentScopes = [],
    Closure|null $parentClosure = null
  ): Repository {
    $parserWhere = new Parser( RepositoryInRuntime::extractTokensFromString( "where", $tokens ), $parentUses, $parentScopes, $parentClosure );
    $parserSelect = new Parser( RepositoryInRuntime::extractTokensFromString( "select", $tokens ), $parentUses, $parentScopes, $parentClosure );
    
    return Repository::inRuntime( 
      array_values( $parserWhere->scopes )[0], $parserWhere, $parserSelect
    );
  }

  public static function indexOf(
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

  private static function extractTokensFromString(
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
}