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
use function sizeof;
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
    $repositoryInRuntime->setWheres( $scopeWheres );
    return $repositoryInRuntime;
  }

  public static function parserVariableToStatic(
    array $tokens = [],
    array $statics = []
  ): array {
    for( $i=0; $i < count($tokens); $i++ ){
      if( $tokens[$i] instanceof ExpToken ){
        $value = $tokens[$i]->value;

        if( is_array( $statics )){
          $staticValue = $statics[
            trim( $value, "$\"" )
          ] ?? null;
        } else
        if( is_object( $statics )){
          $staticValue = $statics->{
            trim( $value, "$\"" )
          } ?? null;
        }

        if( $staticValue !== null ){
          if( is_string( $staticValue )){
            $tokens[$i]->value = $staticValue;
          } else
          if( is_numeric( $staticValue )){
              $tokens[$i]->value = (string)$staticValue;
          } else
          if( is_object( $staticValue )){
            $statics = $staticValue;
            array_splice( $tokens, $i, 2 ); $i--;
          } else
          if( is_array( $staticValue )){
            $statics = $staticValue;
            $tokensOuts = array_splice( $tokens, $i, 4 );
            array_splice( $tokens, $i, 0, [ $tokensOuts[ 2 ]]); $i--;
          }
        }
      }   
    }

    return $tokens;
  } 

  private static function isWithProperty(
    array $values
  ): bool {
    if( sizeof( $values ) < 5 ){
      return false;
    }

    if( sizeof( $values ) === 5 ){
      return $values[0]->key === T_STRING
          && $values[1]->key === T_DOUBLE_COLON
          && $values[2]->key === T_STRING
          && $values[3]->key === T_OBJECT_OPERATOR
          && $values[4]->key === T_STRING;
    }

    return false;
  }  

  private static function isNotProperty(
    array $values
  ): bool {
    if( sizeof( $values ) < 3 ){
      return false;
    }

    if( sizeof( $values ) === 3 ){
      return $values[0]->key === T_STRING
          && $values[1]->key === T_DOUBLE_COLON
          && $values[2]->key === T_STRING;
    }

    return false;
  }
  
  public static function changeEnumValue(
    string $enum,
    string $case,
    string|null $property = null,
    array $uses = []
  ): array {
    $use = $uses[$enum] ?? null;

    if( $use ){
      $constantEnum = "{$use}::{$case}";
      if( defined( $constantEnum )){
        $constantEnum = constant( $constantEnum );
        return [
          $property !== null
            ? new ExpToken(
                T_STRING, $property === "name" 
                  ? $constantEnum->name 
                  : $constantEnum->value, 
                      TokenName::get( T_STRING )
              )
            : new ExpToken(
                T_STRING, 
                $constantEnum->value,
                TokenName::get( T_STRING )
              )
        ];
      }
    } 

    return [];
  }  
  
  public static function parserEnumToStatic(
    array $values = [],
    array $uses = []
  ): array {
    for($i=0; $i<sizeof($values); $i++){
      $withProperty = ParserUtils::sliceStatic( $values, $i, 5 );
      $notProperty = ParserUtils::sliceStatic( $values, $i, 3 );

      $isWithProperty = ExpressionWhereUtils::isWithProperty( $withProperty );
      $isNotProperty = ExpressionWhereUtils::isNotProperty( $notProperty );

      if( $isWithProperty ){
        $result = ExpressionWhereUtils::changeEnumValue(
          $withProperty[0]->value, $withProperty[2]->value, $withProperty[4]->value, $uses
        );

        if( !empty( $result ) ){
          $values[$i] = $result[0];
          array_splice( $values, $i + 1, 4 );
        }
      } else if( $isNotProperty ){
        $result = ExpressionWhereUtils::changeEnumValue(
          $withProperty[0]->value, $withProperty[2]->value, null, $uses
        );

        if( !empty( $result ) ){
          $values[$i] = $result[0];
          array_splice( $values, $i + 1, 2 );
        }
      }      
    };

    return $values;
  }  

  public static function parserValue(
    Parser $parser,
    array $tokens = []
  ): string {
    $tokens = ExpressionWhereUtils::parserVariableToStatic( $tokens, $parser->staticVariables );
    $tokens = ExpressionWhereUtils::parserEnumToStatic( $tokens, $parser->uses );
    return implode( "", $tokens );
  }

  public static function parserParams(
    Parser $parser,
    array $tokens,
    string $columnType
  ): string {
    $values = ParserUtils::mapperStatic( $tokens, fn( ExpToken $expToken ) => $expToken->value );
    return $parser->expressionType->encode( ExpressionWhereUtils::parserValue( $parser, $values ), $columnType );
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