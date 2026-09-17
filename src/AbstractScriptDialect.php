<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\ExpBetween;
use Websyspro\ArrowToSql\Expressions\ExpCompare;
use Websyspro\ArrowToSql\Expressions\ExpDenying;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpGroup;
use Websyspro\ArrowToSql\Expressions\ExpIn;
use Websyspro\ArrowToSql\Expressions\ExpIsNotNull;
use Websyspro\ArrowToSql\Expressions\ExpIsNull;
use Websyspro\ArrowToSql\Expressions\ExpLike;
use Websyspro\ArrowToSql\Expressions\ExpLogical;
use Websyspro\ArrowToSql\Expressions\ExpMethod;
use Websyspro\ArrowToSql\Expressions\ExpNotIn;
use Websyspro\ArrowToSql\Expressions\ExpNotLike;
use Websyspro\ArrowToSql\Expressions\ExpOperator;
use Websyspro\ArrowToSql\Expressions\ExpSeparator;
use Websyspro\ArrowToSql\Expressions\ExpSubQuery;
use Websyspro\ArrowToSql\Expressions\ExpToken;
use function sprintf;

class AbstractScriptDialect
extends SqlUtils
{
  public array $params = [];

  public function __construct(
    private ExpressionType $expressionType,
    private array $statics
  ){}

  public function resolveMethodToDate(
    string $column
  ): string {
    return "Date({$column})";
  }

  public function resolveMethodToUpper(
    string $column
  ): string {
    return "Upper({$column})";
  }
  
  public function resolveMethodToLower(
    string $column
  ): string {
    return "Lower({$column})";
  }
  
  public function resolveMethodToTrim(
    string $column
  ): string {
    return "Trim({$column})";
  }  

  public function resolveExprField(
    ExpField $expr
  ): string {
    if( $expr instanceof ExpField ){
      $modifys = $this->filter(
        $expr->methods, fn( ExpFieldMethod $expr ) => (
          $expr->type === MethodType::Modify
        )
      );

      $column = sprintf(
        "%s.%s", $expr->table, $expr->column
      );
      
      foreach( $modifys as $modify ){
        if( $modify instanceof ExpFieldMethod ){
          $column = match( $modify->name ){
            MethodList::ToDate->value => $this->resolveMethodToDate( $column ),
            MethodList::Upper->value => $this->resolveMethodToUpper( $column ),
            MethodList::Lower->value => $this->resolveMethodToLower( $column ),
            MethodList::Trim->value => $this->resolveMethodToTrim( $column ),
          };
        }
      }

      return $column;
    } else return "table.field";
  }
  
  public function variableToStatic(
    array $tokens = [],
    array $statics = []
  ): array {
    for( $i=0; $i < count($tokens); $i++ ){
      if( $tokens[$i] instanceof ExpToken ){
        $value = $tokens[$i]->value;

        if( is_array( $statics )){
          $staticValue = $statics[
            trim( $value, "$" )
          ] ?? null;
        } else
        if( is_object( $statics )){
          $staticValue = $statics->{
            trim( $value, "$" )
          } ?? null;
        }
        
        if( $staticValue !== null ){
          if( is_string( $staticValue )){
            $tokens[$i]->value = $staticValue;
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

  private function isWithProperty(
    array $values
  ): bool {
    if( count( $values ) < 5 ){
      return false;
    }

    if( count( $values ) === 5 ){
      return $values[0]->key === T_STRING
          && $values[1]->key === T_DOUBLE_COLON
          && $values[2]->key === T_STRING
          && $values[3]->key === T_OBJECT_OPERATOR
          && $values[4]->key === T_STRING;
    }

    return false;
  }  
  
  private function isNotProperty(
    array $values
  ): bool {
    if( count( $values ) < 3 ){
      return false;
    }

    if( count( $values ) === 3 ){
      return $values[0]->key === T_STRING
          && $values[1]->key === T_DOUBLE_COLON
          && $values[2]->key === T_STRING;
    }

    return false;
  }
  
  public function changeEnumValue(
    string $enum,
    string $case,
    string|null $property = null,
    array $uses = []
  ): array {
    $use = $uses[$enum];

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
                      $this->resolveTokenName( T_STRING )
              )
            : new ExpToken(
                T_STRING, 
                $constantEnum->value,
                $this->resolveTokenName(T_STRING)
              )
        ];
      }
    } 

    return [];
  }  

  public function enumToStatic(
    array $values
  ): array {
    for($i=0; $i<count($values); $i++){
      $withProperty = $this->slice( $values, $i, 5 );
      $notProperty = $this->slice( $values, $i, 3 );

      $isWithProperty = $this->isWithProperty( $withProperty );
      $isNotProperty = $this->isNotProperty( $notProperty );

      if( $isWithProperty ){
        $values[$i] = $this->changeEnumValue(
          $withProperty[0]->value,
          $withProperty[2]->value,
          $withProperty[4]->value
        );
      } else if( $isNotProperty ){
        $values[$i] = $this->changeEnumValue(
          $withProperty[0]->value,
          $withProperty[2]->value
        );
      } 
      
      if( $isWithProperty ){
        array_splice( $values, $i + 1, 4 );
      } else if( $isNotProperty ) {
        array_splice( $values, $i + 1, 2 );
      }      
    };

    return $values;
  } 

  public function resolveParams(
    array $tokens,
    mixed $expr,
    string $type,
    ExpressionType $exprType
  ): string {
    if( $expr === ExpIn::class || $expr === ExpNotIn::class ){
      $this->mapper( $tokens, function( ExpToken $expToken ) use( $type, $exprType ){
        if( in_array( $expToken->key, [ T_START_PARENTESES, T_END_PARENTESES, T_COMMA ]) === false){
          $this->params[] = $exprType->encode( $expToken->value, $type );
            $expToken->value = "?";
        }

        return $expToken;
      });

      return implode( "", $this->mapper( $tokens, fn( ExpToken $expToken ) => $expToken->value ));
    } else {
      $this->params[] = $exprType->encode(
        implode( "", $this->mapper( $tokens, fn( ExpToken $expToken ) => $expToken->value )), $type
      );

      return "?";
    }
  }  

  public function resolveExprValue(
    array $tokens
  ): array {
    $tokens = $this->variableToStatic( $tokens, $this->statics );
    $tokens = $this->enumToStatic( $tokens );
    return $tokens;
  } 

  public function resolveExprWhereLogical(
    ExpLogical $expr
  ): string {
    return $expr->value;
  }

  public function resolveExprWhereCompare(
    ExpCompare $expr
  ): string {
    [ $exprField, $exprEqual, $exprValue
    ] = $expr->childs;

    return sprintf( "%s %s %s", 
      $this->resolveExprField( $exprField ), $exprEqual->value, 
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValue->childs
        ), ExpCompare::class, $exprField->type, $this->expressionType
      )
    );
  }
  
  private function resolveExprWhereIsNull(
    ExpIsNull $expr
  ): string {
    [ $exprField ] = $expr->childs;
    return sprintf( "%s Is Null", $this->resolveExprField( $exprField ));
  }

  private function resolveExprWhereIsNotNull(
    ExpIsNotNull $expr
  ): string {
    [ $exprField ] = $expr->childs;
    return sprintf( "%s Is Not Null", $this->resolveExprField( $exprField ));
  }  
  
  private function resolveExprWhereLike(
    ExpLike $expr
  ): string {
    [ $exprField, $exprValue 
    ] = $expr->childs;
    
    return sprintf( "%s Like %s",
      $this->resolveExprField( $exprField ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValue->childs
        ), ExpLike::class, $exprField->type, $this->expressionType
      )
    );
  }

  private function resolveExprWhereNotLike(
    ExpNotLike $expr
  ): string {
    [ $exprField, $exprValue
    ] = $expr->childs;
    
    return sprintf( "%s Not Like %s",
      $this->resolveExprField( $exprField ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValue->childs
        ), ExpNotLike::class, $exprField->type, $this->expressionType
      )
    );
  }  
  
  public function resolveExprWhereBetween(
    ExpBetween $expr
  ): string {
    [ $exprField, $exprValueFrom, $exprValueTo 
    ] = $expr->childs;
    
    return sprintf( "%s Between %s And %s", 
      $this->resolveExprField( $exprField ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValueFrom->childs
        ), ExpBetween::class, $exprField->type, $this->expressionType
      ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValueTo->childs
        ), ExpBetween::class, $exprField->type, $this->expressionType
      )
    );
  }
  
  private function resolveExprWhereDenying(
    ExpDenying $expr
  ): string {
    return sprintf( "Not (%s)", $this->resolveExprWhere( $expr->childs ));
  }  

  private function resolveExprWhereGroup(
    ExpGroup $expr
  ): string {
    return sprintf( "(%s)", $this->resolveExprWhere( $expr->childs ));
  }

  private function resolveExprWhereIn(
    ExpIn $expr
  ): string {
    [ $exprField, $exprValue
    ] = $expr->childs;
    
    return sprintf( "%s In %s", 
      $this->resolveExprField( $exprField ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValue->childs
        ), ExpIn::class, $exprField->type, $this->expressionType
      )
    );
  }
  
  private function resolveExprWhereNotIn(
    ExpNotIn $expr
  ): string {
    [ $exprField, $exprValue 
    ] = $expr->childs;
    
    return sprintf( "%s Not In %s",
      $this->resolveExprField( $exprField ),
      $this->resolveParams( 
        $this->resolveExprValue(
          $exprValue->childs
        ), ExpNotIn::class, $exprField->type, $this->expressionType
      )
    );
  }
  
  private function resolveExprWhereSubQuery(
    ExpSubQuery $expr
  ): string {
    return sprintf( " %s (Select 1 From %s Where %s)", 
      match($expr->method){
        "any" => "Exists",
          default => "" 
      }, $expr->table, $this->resolveExprWhere( $expr->childs )
    );
  }  

  public function resolveExprWhere(
    array $tokens = []
  ): string {
    $tokens = $this->mapper(
      $tokens, function( mixed $expr ){
        if( $expr instanceof ExpLogical ){
          return $this->resolveExprWhereLogical( $expr );
        } else
        if( $expr instanceof ExpCompare ){
          return $this->resolveExprWhereCompare( $expr );
        } else
        if( $expr instanceof ExpIsNull ){
          return $this->resolveExprWhereIsNull( $expr );
        } else
        if( $expr instanceof ExpIsNotNull ){
          return $this->resolveExprWhereIsNotNull( $expr );
        } else
        if( $expr instanceof ExpLike ){
          return $this->resolveExprWhereLike( $expr );
        } else
        if( $expr instanceof ExpNotLike ){
          return $this->resolveExprWhereNotLike( $expr );
        } else
        if( $expr instanceof ExpBetween ){
          return $this->resolveExprWhereBetween( $expr );
        } else
        if( $expr instanceof ExpDenying ){
          return $this->resolveExprWhereDenying( $expr );
        } else
        if( $expr instanceof ExpGroup ){
          return $this->resolveExprWhereGroup( $expr );
        } else
        if( $expr instanceof ExpIn ){
          return $this->resolveExprWhereIn( $expr );
        } else
        if( $expr instanceof ExpNotIn ){
          return $this->resolveExprWhereNotIn( $expr );
        } else
        if( $expr instanceof ExpSubQuery ){
          return $this->resolveExprWhereSubQuery( $expr );
        }

        return $expr->object;
      }
    );

    return implode( " ", $tokens );
  }

  private function resolveExprColumnGroup(
    ExpGroup $expr
  ): string {
    return sprintf( "(%s)", $this->resolveExprColumns( $expr->childs ));
  }
  
  private function resolveExprColumnField(
    ExpField $expr
  ): string {
    return $this->resolveExprField( $expr );
  }

  private function resolveExprColumnMethod(
    ExpMethod $expr
  ): string {
    return sprintf( "Sum(%s)", $this->resolveExprColumns( $expr->childs ));
  }

  private function resolveExprColumnSeparator(
    ExpSeparator $expr
  ): string {
    return $expr->value;
  }
  
  private function resolveExprColumnOperator(
    ExpOperator $expr
  ): string {
    return $expr->type;
  }  

  public function resolveExprColumns(
    array $tokens = []
  ): string {
    $tokens = $this->mapper(
      $tokens, function( mixed $expr ){
        if( $expr instanceof ExpGroup ){
          return $this->resolveExprColumnGroup( $expr );
        } else
        if( $expr instanceof ExpField ){
          return $this->resolveExprColumnField( $expr );
        } else
        if( $expr instanceof ExpMethod ){
          return $this->resolveExprColumnMethod( $expr );
        } else
        if( $expr instanceof ExpSeparator ){
          return $this->resolveExprColumnSeparator( $expr );
        } else
        if( $expr instanceof ExpOperator ){
          return $this->resolveExprColumnOperator( $expr );
        } 

        return $expr->object;
      }
    );

    return preg_replace("#\s+,#", ",", implode( " ", $tokens ));
  }  

  public function resolveExprParams(
  ): array {
    return $this->params;
  }
}