<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\AbstractExpTypeFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldCaseMethod;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpMethod;
use Websyspro\ArrowToSql\Expressions\ExpToken;
use Websyspro\Entity\Decorators\EntityList;
use function array_slice;
use function in_array;
use function defined;
use function sizeof;
use Closure;

defined( 'T_START_PARENTESES' ) || define( 'T_START_PARENTESES', 40 );
defined( 'T_END_PARENTESES' ) || define( 'T_END_PARENTESES', 41 );
defined( 'T_START_BRACKET' ) || define( 'T_START_BRACKET', 91 );
defined( 'T_END_BRACKET' ) || define( 'T_END_BRACKET', 93 );
defined( 'T_START_BRACE' ) || define( 'T_START_BRACE', 123 );
defined( 'T_END_BRACE' ) || define( 'T_END_BRACE', 125 );
defined( 'T_DOT' ) || define( 'T_DOT', 46 );
defined( 'T_COMMA' ) || define( 'T_COMMA', 44 );
defined( 'T_SEMICOLON' ) || define( 'T_SEMICOLON', 59 );
defined( 'T_COLON' ) || define( 'T_COLON', 58 );
defined( 'T_QUESTION' ) || define( 'T_QUESTION', 63 );
defined( 'T_PLUS' ) || define( 'T_PLUS', 43 );
defined( 'T_MINUS' ) || define( 'T_MINUS', 45 );
defined( 'T_MULTIPLY' ) || define( 'T_MULTIPLY', 42 );
defined( 'T_DIVIDE' ) || define( 'T_DIVIDE', 47 );
defined( 'T_EQUAL' ) || define( 'T_EQUAL', 61 );
defined( 'T_GREATER_THAN' ) || define( 'T_GREATER_THAN', 62 );
defined( 'T_LESS_THAN' ) || define( 'T_LESS_THAN', 60 );
defined( 'T_NOT' ) || define( 'T_NOT', 33 );

class ParserUtils
{
  public AbstractScriptDialect $abstractScriptDialect;

  public function __construct(
    public Parser $parser,
    public Repository $repository
  ){}

  public function slice(
    array $items,
    int $offset,
    int|null $length = null
  ): array {
    return array_slice( $items, $offset, $length );
  } 
  
  public static function sliceStatic(
    array $items,
    int $offset,
    int|null $length = null
  ): array {
    return array_slice( $items, $offset, $length );
  }  

  public function mapper(
    array $items,
    Closure $closure
  ): array {
    $result = [];

    foreach( $items as $key => $item ){
      $result[$key] = $closure($item, $key);
    }

    return $result;
  }

  public static function mapperStatic(
    array $items,
    Closure $closure
  ): array {
    $result = [];

    foreach( $items as $key => $item ){
      $result[$key] = $closure($item, $key);
    }

    return $result;
  }  

  public function filter(
    array $items,
    Closure $closure
  ): array {
    $result = [];

    foreach( $items as $item ){
      if( $closure( $item )){
        $result[] = $item;
      }
    }

    return $result;
  }

  public static function where(
    array $items,
    Closure $closure
  ): array {
    $result = [];

    foreach( $items as $item ){
      if( $closure( $item )){
        $result[] = $item;
      }
    }

    return $result;
  }  
  
  public function indexOf(
    array $tokens,
    int $type
  ): int {
    foreach( $tokens as $cursor => $token ){
      if( $token->key === $type ){
        return $cursor;
      }
    }

    return -1;
  }

  public static function indexOfStatic(
    array $tokens,
    int $type
  ): int {
    foreach( $tokens as $cursor => $token ){
      if( $token->key === $type ){
        return $cursor;
      }
    }

    return -1;
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

      $isWithProperty = ParserUtils::isWithProperty( $withProperty );
      $isNotProperty = ParserUtils::isNotProperty( $notProperty );

      if( $isWithProperty ){
        $result = ParserUtils::changeEnumValue(
          $withProperty[0]->value, $withProperty[2]->value, $withProperty[4]->value, $uses
        );

        if( !empty( $result ) ){
          $values[$i] = $result[0];
          array_splice( $values, $i + 1, 4 );
        }
      } else if( $isNotProperty ){
        $result = ParserUtils::changeEnumValue(
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
  ): array {
    $tokens = ParserUtils::parserVariableToStatic( $tokens, $parser->staticVariables );
    $tokens = ParserUtils::parserEnumToStatic( $tokens, $parser->uses );
    return $tokens;
  }  
  
  public static function extractTokensFromStr(
    string $value,
    array $tokens,
    int $cursor = -1
  ): array {
    foreach( $tokens as $cursor => $token ){
      if( $token->key === T_STRING && $token->value === $value ){
        $tokens = ParserUtils::sliceStatic( $tokens, bcadd( $cursor, 2 ));
        return Parser::contextsNotEndsStatic( $tokens );
      }
    }

    return [];
  }

  public static function isTypeGroup(
    array $tokens = []
  ): bool {
    return $tokens[0]->key === T_START_PARENTESES;
  }

  public static function isTypeColumnMethod(
    array $tokens = []
  ): bool {
    if( sizeof( $tokens ) < 3 ){
      return false;
    }

    $isColumnClosure = in_array( 
      $tokens[2]->value, [ 
        MethodList::Sum->value, 
        MethodList::Count->value,
        MethodList::Avg->value,
        MethodList::Min->value,
        MethodList::Max->value,
        MethodList::Concat->value
      ]
    ) === false;

    if( $isColumnClosure ){
      return false;
    }

    return $tokens[0]->key === T_VARIABLE
        && $tokens[1]->key === T_OBJECT_OPERATOR
        && $tokens[2]->key === T_STRING
        && $tokens[3]->key === T_START_PARENTESES;
  }
  
  public static function isTypeColumnSeparator(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_COMMA
    ]);
  }  

  public static function isField(
    array $tokens = [],
    array $columnsItems = []
  ): bool {
    if( sizeof( $tokens ) < 3 ){
      return false;
    }

    $entityStructureColumns = ParserUtils::where(
      $columnsItems, fn( string $column ) => (
        $tokens[2]->value === $column
      )
    );

    $hasStructureTokens = (
      $tokens[0]->key === T_VARIABLE && 
      $tokens[1]->key === T_OBJECT_OPERATOR && 
      $tokens[2]->key === T_STRING
    );

    return empty($entityStructureColumns) !== 0 && $hasStructureTokens;
  }  

  public static function isTypeColumnField(
    array $tokens = []
  ): bool {
    return ParserUtils::isField( $tokens );
  }  

  public static function isTypeColumnOperator(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_MULTIPLY, T_PLUS, T_MINUS, T_DIVIDE
    ]);
  }  

  public static function whereClousureByType(
    ExpField|ExpMethod $expr,
    array $methodList = [ MethodType::Modify, MethodType::Alias ]
  ): array {
    return ParserUtils::where(
      $expr->closures, fn( ExpFieldMethod $expr ) => (
        in_array( $expr->type, $methodList )
      )
    );    
  }  

  public function filterMethodsArr(
    ExpField|ExpMethod $expr,
    array $methodList = [ MethodType::Modify, MethodType::Alias ]
  ): array {
    return $this->filter(
      $expr->closures, fn( AbstractExpTypeFieldMethod $expr ) => (
        in_array( $expr->type, $methodList )
      )
    );    
  }  

  public function groupByTypes(
    array $type,
    array $tokens,
     bool $showKey = false,
    array $curr = [],
    array $accu = [],
      int $depth = 0
  ): array {
    foreach( $tokens as $token ){
      if( in_array( $token->key, $type ) && $depth === 0){
        if( $curr ){
          $accu[] = $curr;
          $curr = [];
        } 
        
        if( $showKey ){
          $accu[] = [ $token ];
        }

        continue;
      }

      $curr[] = $token;

      if($token->key === T_START_PARENTESES) $depth++;
      if($token->key === T_END_PARENTESES) $depth--;
    }

    if( $curr ){
      $accu[] = $curr;
    }

    return $accu;
  }

  public static function groupByTypesStatic(
    array $type,
    array $tokens,
     bool $showKey = false,
    array $curr = [],
    array $accu = [],
      int $depth = 0
  ): array {
    foreach( $tokens as $token ){
      if( in_array( $token->key, $type ) && $depth === 0){
        if( $curr ){
          $accu[] = $curr;
          $curr = [];
        } 
        
        if( $showKey ){
          $accu[] = [ $token ];
        }

        continue;
      }

      $curr[] = $token;

      if($token->key === T_START_PARENTESES) $depth++;
      if($token->key === T_END_PARENTESES) $depth--;
    }

    if( $curr ){
      $accu[] = $curr;
    }

    return $accu;
  }  

  public function groupByTypesLogical(
    array $tokens = []
  ): array {
    return $this->groupByTypes([ 
      T_LOGICAL_AND, T_LOGICAL_OR, T_BOOLEAN_AND, T_BOOLEAN_OR 
    ], $tokens, true );
  }
  
  public function groupByTypesColumn(
    array $tokens = []
  ): array {
    return $this->groupByTypes([ 
      T_COMMA, T_MULTIPLY, T_PLUS, T_MINUS, T_DIVIDE
    ], $tokens, true );
  }  
  
  public function groupByTypesComma(
    array $tokens = []
  ): array {
    return $this->groupByTypes([ T_COMMA ], $tokens, true );
  }  

  public function contextsNotEnds(
    array $tokens,
    int $parenteses = 0
  ): array {
    for($i=0; $i<count($tokens); $i++){
      if($tokens[$i]->key === T_START_PARENTESES){
        $parenteses++;
      }

      if($tokens[$i]->key === T_END_PARENTESES){
        $parenteses--;

        if($parenteses < 0){
          $tokens = array_slice(
            $tokens, 0, $i
          ); break;
        }          
      }

      if($parenteses < 1){
        if($tokens[$i]->key === T_SEMICOLON){
          $tokens = array_slice(
            $tokens, 0, $i
          ); break;
        }
      }
    };

    if( $tokens[0]->key === T_START_BRACKET ){
      if( $tokens[count($tokens) - 1]->key === T_END_BRACKET ){
        $tokens = $this->slice( $tokens, 1, -1 );
      }
    }

    return $tokens;
  }

  public static function contextsNotEndsStatic(
    array $tokens,
    int $parenteses = 0
  ): array {
    for($i=0; $i<count($tokens); $i++){
      if($tokens[$i]->key === T_START_PARENTESES){
        $parenteses++;
      }

      if($tokens[$i]->key === T_END_PARENTESES){
        $parenteses--;

        if($parenteses < 0){
          $tokens = array_slice(
            $tokens, 0, $i
          ); break;
        }          
      }

      if($parenteses < 1){
        if($tokens[$i]->key === T_SEMICOLON){
          $tokens = array_slice(
            $tokens, 0, $i
          ); break;
        }
      }
    };

    if( $tokens[0]->key === T_START_BRACKET ){
      if( $tokens[count($tokens) - 1]->key === T_END_BRACKET ){
        $tokens = ParserUtils::sliceStatic( $tokens, 1, -1 );
      }
    }

    return $tokens;
  }  

  public function getExprFieldMethods(
    string $column,
    ExpFieldMethod $method
  ): string {
    return match( $method->descriptor ){
      MethodList::ToDate->value => ScriptDialect::get()->resolveMethodToDate( $column ),
      MethodList::Upper->value => ScriptDialect::get()->resolveMethodToUpper( $column ),
      MethodList::Lower->value => ScriptDialect::get()->resolveMethodToLower( $column ),
      MethodList::Trim->value => ScriptDialect::get()->resolveMethodToTrim( $column ),
        default => $column
    };
  }  

  public function resolveFieldMethodType(
    string $method,
    array $methodArr = []
  ): MethodType {
    $methodArr = [
      MethodList::StartsWith->value,
      MethodList::EndsWith->value,
      MethodList::Contains->value,
      MethodList::Between->value,
      MethodList::NotIn->value,
      MethodList::In->value,
      MethodList::Equals->value
    ];

    if( in_array( $method, $methodArr )){
      return MethodType::Compare;
    }

    return $method === MethodList::As->value 
      ? MethodType::Alias 
      : MethodType::Modify;
  }  
  
  public function resolveField(
    array $childs = []
  ): ExpField {
    $entityStructureColumn = (string)$childs[2]->value;

    $columnMethods = $this->mapper(
      $this->groupByTypes(
        [ T_OBJECT_OPERATOR ], $this->slice(
          $childs, 4
        )
      ), fn( array $methods ) => (
        new ExpFieldMethod(
          $methods[0]->value, 
          $this->resolveFieldMethodType( $methods[0]->value ),
          $this->slice( $methods, 2, -1 )
        )
      )
    );

    $entityStructure = $this->repository->getEntityStructureByString(
      $this->parser->scopes[ (string)$childs[0]->value ]
    );

    $columnType = $entityStructure->getColumnType( $entityStructureColumn );
    $columnName = ( $columnType === EntityList::class ) 
      ? $entityStructureColumn : $entityStructure->getColumnName( $entityStructureColumn );

    return new ExpField(
      $entityStructure->getEntityAlias(),
      $columnName,
      $columnType, 
      $columnMethods
    );
  }

  public static function createRepositoryInRuntimeFromIncludes(
    Parser $parser,
    Repository $repository
  ): RepositoryInRuntime|null {
    if( sizeof( $parser->tokens ) < 3 ){
      return null;
    }

    [ $propertyFromTokens ] = ParserUtils::sliceStatic( $parser->tokens, 2, 1 );
    if( $propertyFromTokens && $propertyFromTokens instanceof ExpToken ){
      $entityToRepository = $repository->getEntityStructure()->typesAlls->items[ $propertyFromTokens->value ];
      if( $entityToRepository === EntityList::class ){
        $entityToRepository = $repository->getEntityStructure()->entityLists->items[ $propertyFromTokens->value ];
      }
    }

    $repositoryInRuntime = new RepositoryInRuntime($entityToRepository, $parser->uses, $parser->scopes, $parser->getClosure());
    $repositoryInRuntimeWhereTokens = ParserUtils::extractTokensFromStr( "where", $parser->tokens );
    $repositoryInRuntimeSelectTokens = ParserUtils::extractTokensFromStr( "select", $parser->tokens );
    $repositoryInRuntimeIncludesTokens = ParserUtils::groupByTypesStatic( [ T_COMMA ], ParserUtils::extractTokensFromStr( "include", $parser->tokens ));

    if( !empty( $repositoryInRuntimeWhereTokens )) $repositoryInRuntime->registerWheres( $repositoryInRuntimeWhereTokens );
    if( !empty( $repositoryInRuntimeSelectTokens )) $repositoryInRuntime->registerSelect( $repositoryInRuntimeSelectTokens );
    if( !empty( $repositoryInRuntimeIncludesTokens )) $repositoryInRuntime->registerIncludes( $repositoryInRuntimeIncludesTokens );
      
    $repositoryInRuntime->registerRelationship( $repository );
    return $repositoryInRuntime;
  }
}