<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Expressions\ExpToken;

class ExpressionIncludeUtils
{
  public static function extractScopeWhere(
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

    return [ "", [] ];
  }
}