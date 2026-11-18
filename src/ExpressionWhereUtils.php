<?php

namespace Websyspro\ArrowToSql;

use Closure;
use Exception;
use Websyspro\ArrowToSql\Expressions\ExpToken;

class ExpressionWhereUtils
{
  public static function extractEntityAndWhere(
    array $tokens
  ): array {
    [ $scopes, $scopeWheres ] = [
      ParserUtils::sliceStatic( 
        $tokens, ParserUtils::indexOfStatic( 
          $tokens, T_FN
        ) + 2, 1
      ),
      ParserUtils::contextsNotEndsStatic(
        ParserUtils::sliceStatic( 
          $tokens, ParserUtils::indexOfStatic( 
            $tokens, T_FN 
          )
        )
      ),
    ];

    [ $scope ] = $scopes;

    if( $scope instanceof ExpToken ){
      return [ $scope->value, $scopeWheres ];
    }

    return throw new Exception( "SCOPE from subquery not found" );
  }

  public static function createRepositoryInRunTime(
    array $tokens,
    array $uses,
    array $scopes,
    Closure $closure
  ): RepositoryInRuntime {
    [ $scope, $scopeWheres ] = $tokens;
    $repositoryInRuntime = new RepositoryInRuntime( $uses[$scope], $uses, $scopes, $closure );
    $repositoryInRuntime->setWheres( $scopeWheres );
    return $repositoryInRuntime;
  }
}