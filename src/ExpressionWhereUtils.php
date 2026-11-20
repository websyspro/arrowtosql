<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\LogicalType;
use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\ExpEqual;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpLogical;
use Websyspro\ArrowToSql\Expressions\ExpToken;
use Websyspro\ArrowToSql\Expressions\ExpValue;
use Websyspro\Entity\Decorators\EntityList;
use function in_array;
use Exception;
use Closure;

class ExpressionWhereUtils
{
  public static function extractScopeName(
    array $tokens
  ): string|null {
    [ $expToken ] = ParserUtils::sliceStatic( $tokens, 4, 1 );
    if( $expToken instanceof ExpToken ){
      return $expToken->value;
    }

    return null;
  }

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
    $repositoryInRuntime->registerWheres( $scopeWheres );
    return $repositoryInRuntime;
  }

  public static function parserParams(
    Parser $parser,
    array $tokens,
    string $columnType
  ): string {
    $values = ParserUtils::parserValue( $parser, $tokens );
    [ $value ] = ParserUtils::mapperStatic( $values, fn( ExpToken $expToken ) => $expToken->value );
    return $parser->expressionType->encode( $value, $columnType );
  }

  public static function fieldMethodType(
    string $closure,
    array $closures = []
  ): MethodType {
    $closures = [
      MethodList::StartsWith->value,
      MethodList::EndsWith->value,
      MethodList::Contains->value,
      MethodList::Between->value,
      MethodList::NotIn->value,
      MethodList::In->value,
      MethodList::Equals->value
    ];

    if( in_array( $closure, $closures )){
      return MethodType::Compare;
    }

    return $closure === MethodList::As->value 
      ? MethodType::Alias 
      : MethodType::Modify;
  }

  public static function groupByTypesFromCompare(
    array $tokens = []
  ): array {
    return ParserUtils::groupByTypesStatic([ 
      T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL,
      T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL,
      T_LESS_THAN, T_GREATER_THAN, T_EQUAL
    ], $tokens, true );
  }
  
  public static function createASTExpField(
    array $tokens,
    Parser $parser,
    Repository $repository
  ): ExpField {
    $entityStructureColumn = $tokens[2]->value;

    $columnMethods = ParserUtils::mapperStatic(
      ParserUtils::groupByTypesStatic(
        [ T_OBJECT_OPERATOR ], ParserUtils::sliceStatic(
          $tokens, 4
        )
      ), fn( array $closure ) => (
        new ExpFieldMethod(
          $closure[0]->value, 
          ExpressionWhereUtils::fieldMethodType( $closure[0]->value ),
          ParserUtils::sliceStatic( $closure, 2, -1 )
        )
      )
    );

    $entityStructure = $repository->getEntityStructureByString(
      $parser->scopes[ $tokens[0]->value ]
    );

    $columnType = $entityStructure->getColumnType( $entityStructureColumn );
    $columnName = ( $columnType === EntityList::class ) 
      ? $entityStructureColumn : $entityStructure->getColumnName( $entityStructureColumn );

    return new ExpField(
      $entityStructure->getEntityAlias(),
      $columnName, $columnType, $columnMethods
    );
  } 
  
  public static function createASTExpEqual(
    array $tokens = []
  ): ExpEqual {
    return new ExpEqual(
      match( $tokens[0]->value ){
        "===", "==" => "=",
        "!==", "!=" => "!=",
          default => $tokens[0]->value
      }
    );
  }  

  public static function createASTExpValue(
    array $tokens,
    string $columnType,
    Parser $parser,
  ): ExpValue {
    return new ExpValue( value: ExpressionWhereUtils::parserParams( $parser, $tokens, $columnType ));
  }
  
  public static function createASTExpLogical(
    array $tokens = []
  ): ExpLogical {
    return new ExpLogical(
      match( $tokens[0]->key ){
        T_LOGICAL_AND, T_BOOLEAN_AND => LogicalType::And->value,
        T_LOGICAL_OR, T_BOOLEAN_OR => LogicalType::Or->value
      }
    );
  }  
}