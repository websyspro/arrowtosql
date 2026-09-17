<?php

namespace Websyspro\ArrowToSql;

use Closure;
use PhpToken;
use ReflectionFunction;
use Websyspro\ArrowToSql\Enums\LogicalType;
use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\WhereResult;
use Websyspro\ArrowToSql\Expressions\ExpBetween;
use Websyspro\ArrowToSql\Expressions\ExpCompare;
use Websyspro\ArrowToSql\Expressions\ExpDenying;
use Websyspro\ArrowToSql\Expressions\ExpEqual;
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
use Websyspro\ArrowToSql\Expressions\ExpUnary;
use Websyspro\ArrowToSql\Expressions\ExpValue;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Types\ColumnFlag;
use function defined;
use function sprintf;
use function count;
use function in_array;

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

class ArrowToSql 
extends SqlUtils
{
  private ReflectionFunction $reflection;
  private ExpressionType $expressionType;
  private array $rows = [];
  public array $uses = [];
  public array $statics = [];
  public array $expression = [];
  public array $scopes = [];
  public array $tokens = [];
  public array $params = [];
  public array|string $script;

  private mixed $scriptDialect;

  public function __construct(
    private Closure $closure  
  ){}

  private function resolveExpressionType(
  ): void {
    $this->expressionType = new ExpressionType();
  }

  private function resolveReflection(
  ): void {
    $this->reflection = new ReflectionFunction(
      $this->closure
    );    
  } 

  private function resolveRows(
  ): void {
    $this->rows = file(
      $this->reflection->getFileName(),
        FILE_IGNORE_NEW_LINES
    );
  }  
  
  private function resolveUses(
  ): void {
    foreach($this->rows as $row){
      $row = trim($row);

      if(preg_match("#^(class|function|abstract|final)\s#", $row)){
        break;
      }

      if(preg_match("#^use\s+(.+);$#", $row, $matches)){
        $fqcn = trim($matches[1]);

        if(preg_match('/^(.+)\s+as\s+(\w+)$/i', $fqcn, $aliasParts)) {
          $alias = trim($aliasParts[2]);
          $fqcn = trim($aliasParts[1]);
        } else {
          $parts = explode('\\', $fqcn);
          $alias = end($parts);
        }

        $this->uses[$alias] = $fqcn;
      }
    }
  }  

  private function resolveStatics(
  ): void {
    $this->statics = $this->reflection
      ->getStaticVariables();    
  }

  private function resolveExpression(
  ): void {
    $this->expression = $this->mapper(
      $this->slice(
        $this->rows, $this->reflection->getStartLine() - 1, 
        $this->reflection->getEndLine() - $this->reflection->getStartLine() + 1
      ), function( string $line ){
        if( strpos( $line, "//" ) !== false ){
          $line = substr( 
            $line, 0, strpos(
              $line, "//"
            )
          );
        }

        return $line;
      }
    );

    $this->expression = $this->slice(
      PhpToken::tokenize(
        sprintf( "<?php %s", implode( " ", $this->expression ))
      ), 1
    );

    $this->expression = $this->mapper(
      $this->expression, fn( PhpToken $phpToken ) => new ExpToken( 
        $phpToken->id, $phpToken->text, $this->resolveTokenName( $phpToken->id )
      )
    );    

    $this->expression = $this->filter(
      $this->expression, fn( ExpToken $token ) => $token->key !== T_WHITESPACE
    );

    $this->expression = $this->slice(
      $this->expression, $this->indexOf( $this->expression, T_FN ) + 1
    );
  } 

  private function resolveEntityStructure(
    array $childs = []
  ): EntityStructure {
    return new EntityStructure(
      $this->scopes[ $childs[0]->value ]
    );
  }   

  private function resolveSubQueryMethod(
    array $tokens = []
  ): string|null {
    $tokens = $this->slice(
      $tokens, $this->indexOf( $tokens, T_FN ) - 2, 1
    );

    if( empty( $tokens )){
      return null;
    }

    [ $token ] = $tokens;
    return $token->value ?? null;    
  }

  private function resolveSubQueryTable(
    array $tokens = []
  ): string {
    $tokens = $this->slice(
      $tokens, $this->indexOf( 
        $tokens, T_FN
      ) + 3, 1
    );

    return $this->resolveEntityStructure($tokens)->table;
  }

  private function resolveScopesAndTokens(
    array|null $expression = null
  ): array {
    if( $expression === null ){
      $expression = $this->expression;
    }

    $this->mapper(
      $this->groupByTypesComma(
        $this->slice( $expression, $this->indexOf( $expression, T_START_PARENTESES ) + 1,
          $this->indexOf( $expression, T_END_PARENTESES ) - 1
        )
      ), function( array $groupTokens ){
        [ $tokenEntity, $tokenVar ] = $groupTokens;
        $this->scopes[ $tokenVar->value ] = $this->uses[ $tokenEntity->value ];
      }
    );    

    return $this->contextsNotEnds(
      $this->slice( $expression, $this->indexOf( $expression, T_DOUBLE_ARROW ) + 1)
    );
  }

  private function hierackyTypeDanying(
    array $tokens = []
  ): bool {
    return $tokens[0]->key === T_NOT;
  }

  private function hierackyTypeGroup(
    array $tokens = []
  ): bool {
    return $tokens[0]->key === T_START_PARENTESES;
  }
  
  private function hierackyTypeSubQuery(
    array $tokens = []
  ): bool {
    return in_array( $this->resolveSubQueryMethod( $tokens ), [ "any" ]);
  }
  
  private function hierackyTypeLogical(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_LOGICAL_AND, T_LOGICAL_OR, T_BOOLEAN_AND, T_BOOLEAN_OR 
    ]);
  }

  private function hierackyTypeCompare(
    array $tokens = []
  ): bool {
    $tokens = $this->filter( $tokens, fn(ExpToken $token) => (
      in_array( $token->key, [
        T_IS_NOT_IDENTICAL, T_IS_GREATER_OR_EQUAL, T_IS_SMALLER_OR_EQUAL,
        T_EQUAL, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL,
        T_GREATER_THAN, T_LESS_THAN
      ])
    ));

    return empty( $tokens ) ? false : true;
  }

  private function hierackyTypeUnary(
    array $tokens = []
  ): bool {
    if( count($tokens) < 3 ){
      return false;
    }

    return $this->hierackyTypeCompare($tokens) === false;
  }  

  private function hierackyType(
    array $tokens = []
  ): string|null {
    if( $this->hierackyTypeDanying( $tokens )){
      return ExpDenying::class;
    } else if( $this->hierackyTypeGroup( $tokens )){
      return ExpGroup::class;
    } else if( $this->hierackyTypeSubQuery( $tokens )){
      return ExpSubQuery::class;
    } else if( $this->hierackyTypeLogical( $tokens )){
      return ExpLogical::class;
    } else if( $this->hierackyTypeCompare( $tokens )){
      return ExpCompare::class;
    } else if( $this->hierackyTypeUnary( $tokens )){
      return ExpUnary::class;
    }
      
    return null;    
  }

  private function resolveHierarchyToExpDenying(
    array $childs = [],
  ): ExpDenying {
    return new ExpDenying(
      $this->resolveHierarchy( 
        $this->slice( $childs, 1 )
      )
    );
  }

  private function resolveHierarchyToExpGroup(
    array $childs = []
  ): ExpGroup {
    return new ExpGroup(
      $this->resolveHierarchy( 
        $this->slice( $childs, 1, -1 )
      )
    );
  }
  
  private function resolveHierarchyToExpSubQuery(
   array $childs = []
  ): ExpSubQuery {
    $tokens = $this->resolveScopesAndTokens(
      $this->slice( $childs, $this->indexOf(
        $childs, T_FN ) + 1
      )
    );

    return new ExpSubQuery(
      $this->resolveSubQueryMethod( $childs ),
      $this->resolveSubQueryTable( $childs ),
      $this->resolveHierarchy( $tokens )
    );
  }

  private function resolveHierarchyToExpLogical(
    array $childs = []
  ): ExpLogical {
    return new ExpLogical(
      match( $childs[0]->key ){
        T_BOOLEAN_AND, T_LOGICAL_AND => LogicalType::And->value,
        T_BOOLEAN_OR, T_LOGICAL_OR => LogicalType::Or->value,
          default => ""
      } 
    );
  }

  public function isField(
    array $childs = []
  ): bool {
    if( count( $childs ) < 3 ){
      return false;
    }

    if( isset($this->scopes[$childs[0]->value]) === false ){
      return false;
    }

    $entityStructure = $this->resolveEntityStructure( $childs );
    $entityStructureColumns = $this->filter(
      $entityStructure->columns, fn( EntityColumn $entityColumn ) => (
        $entityColumn->property === $childs[2]->value
      )
    );

    $hasStructureTokens = (
      $childs[0]->key === T_VARIABLE && 
      $childs[1]->key === T_OBJECT_OPERATOR && 
      $childs[2]->key === T_STRING
    );

    return empty($entityStructureColumns) !== 0 && $hasStructureTokens;
  }

  private function resolveFieldMethodType(
    string $method,
    array $methodArr = []
  ): MethodType {
    $methodArr = [
      MethodList::StartsWith->value,
      MethodList::EndsWith->value,
      MethodList::Contains->value,
      MethodList::Between->value,
      MethodList::NotIn->value,
      MethodList::In->value
    ];

    if( in_array( $method, $methodArr )){
      return MethodType::Compare;
    }

    return MethodType::Modify;
  }

  private function resolveField(
    array $childs = []
  ): ExpField {
    $entityStructure = $this->resolveEntityStructure( $childs );
    
    [ $columnDetail ] = $this->filter(
      $entityStructure->columns, fn( EntityColumn $entityColumn ) => (
        $entityColumn->property === (string)$childs[2]->value
      )
    );

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

    return new ExpField(
      $entityStructure->table,
      $columnDetail->column,
      $columnDetail->type,
      $columnMethods
    );
  }

  private function resolveValue(
    array $childs = []
  ): ExpValue {
    return new ExpValue(
      $childs
    );
  }  

  private function resolveEqual(
    array $childs = []
  ): ExpEqual {
    return new ExpEqual(
      match( $childs[0]->value ){
        "===", "==" => "=",
        "!==", "!=" => "<>",
          default => $childs[0]->value
      }
    );
  }  

  private function resolveHierarchyToExpCompare(
    array $childs = []
  ): ExpCompare {
    [ $childA, $equals, $childB 
    ] = $this->groupByTypes([ 
      T_EQUAL,
      T_IS_EQUAL, T_IS_IDENTICAL,
      T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL,
      T_IS_GREATER_OR_EQUAL, T_IS_SMALLER_OR_EQUAL,
      T_GREATER_THAN, T_LESS_THAN 
    ], $childs, true );
    
    [ $childA, $equals, $childB ] = [
      $this->isField( $childA )
        ? $this->resolveField( $childA )
        : $this->resolveValue( $childA ),
            $this->resolveEqual( $equals ),
      $this->isField( $childB )
        ? $this->resolveField( $childB )
        : $this->resolveValue( $childB )  
    ];

    if( $childA instanceof ExpValue ){
      return new ExpCompare(
        [ $childB, $equals->invert(), $childA ]
      );
    }

    return new ExpCompare(
      [ $childA, $equals, $childB ]
    );
  }

  private function resolveHierarchyToExpUnary(
    array $childs = []
  ): ExpUnary {
    if( $this->isField( $childs )){
      $childs = $this->resolveField( $childs );
    }

    return new ExpUnary(
      [ $childs ] 
    );
  }  

  private function resolveHierarchy(
    array $tokens = []
  ): array {
    return $this->mapper(
      $this->groupByTypesLogical( $tokens ), fn( array $childs ) => (
        match( $this->hierackyType( $childs )){
          ExpDenying::class => $this->resolveHierarchyToExpDenying( $childs ),
          ExpGroup::class => $this->resolveHierarchyToExpGroup( $childs ),
          ExpSubQuery::class => $this->resolveHierarchyToExpSubQuery( $childs ),
          ExpLogical::class => $this->resolveHierarchyToExpLogical( $childs ),
          ExpCompare::class => $this->resolveHierarchyToExpCompare( $childs ),
          ExpUnary::class => $this->resolveHierarchyToExpUnary( $childs ),
            default => $childs
        }
      ) 
    );
  }

  private function resolveWildcardLike(
  ): ExpToken {
    return new ExpToken(37, "%", $this->resolveTokenName(T_STRING));
  }

  private function resolveEqualTrue(
  ): ExpToken {
    return new ExpToken(313, "true", $this->resolveTokenName(T_STRING));
  }

  private function resolveEqualFalse(
  ): ExpToken {
    return new ExpToken(313, "false", $this->resolveTokenName(T_STRING));
  }
  
  private function resolveStartParentese(
  ): ExpToken {
    return new ExpToken(40, "(", $this->resolveTokenName(T_START_PARENTESES));
  }  

  private function resolveEndParentese(
  ): ExpToken {
    return new ExpToken(41, ")", $this->resolveTokenName(T_END_PARENTESES));
  }

  private function resolveEntryParenteses(
    array $tokens = []
  ): array {
    return [ 
      $this->resolveStartParentese(), ...$tokens,
      $this->resolveEndParentese()
    ];
  }

  private function resovleIsBetween(
    string $methdoName
  ): bool {
    return in_array( 
      $methdoName, [
        MethodList::Between->value
      ]
    );
  }  

  private function resovleIsLike(
    string $methdoName
  ): bool {
    return in_array( 
      $methdoName, [
        MethodList::Contains->value,
        MethodList::StartsWith->value,
        MethodList::EndsWith->value 
      ]
    );
  }

  private function resolveIsIn(
    string $methdoName
  ): bool {
    return in_array( 
      $methdoName, [
        MethodList::In->value
      ]
    );
  } 
  
  private function resolveIsNotIn(
    string $methdoName
  ): bool {
    return in_array( 
      $methdoName, [
        MethodList::NotIn->value
      ]
    );
  }  

  private function resolveSemanticsMethods(
    array $tokens = [],
    array $modifys = [],
    array $compares = [],
    array $exprLikes = [],
    array $exprValues = []
  ): array {
    foreach($tokens as $tokenIndex => $token){
      if( $token instanceof ExpLogical ){
        continue;
      }

      if( $token instanceof ExpUnary ){
        [ $child ] = $token->childs;

        if( $child instanceof ExpField ){
          if( empty( $child->methods ) === false ){
            $modifys = $this->filter(
              $child->methods, fn( ExpFieldMethod $expr ) => (
                $expr->type === MethodType::Modify
              )
            );

            $compares = $this->filter(
              $child->methods, fn( ExpFieldMethod $expr ) => (
                $expr->type === MethodType::Compare
              )
            );

            foreach( $compares as $comporeIndex => $compare ){
              if( $compare instanceof ExpFieldMethod ){
                if( $this->resovleIsBetween( $compare->name ) || $this->resovleIsLike( $compare->name )){
                  $compares[ $comporeIndex ]->args = $this->groupByTypes(
                    [ T_COMMA ], $compares[ $comporeIndex ]->args, false
                  );
                }

                /* Resolver Is Between */
                if( $this->resovleIsBetween( $compare->name ) ){
                  [ $expTokenBetweenFrom, $expTokenBetweenTo 
                  ] = $compares[ $comporeIndex ]->args;

                  $tokens[ $tokenIndex ] = new ExpBetween(
                    [
                      new ExpField( $child->table, $child->column, $child->type, $modifys ),
                      new ExpValue( $expTokenBetweenFrom ), new ExpValue( $expTokenBetweenTo )
                    ]
                  );
                }

                /* Resolver Is Contains, startWith, endWith */
                if( $this->resovleIsLike( $compare->name )){
                  foreach( $compares[ $comporeIndex ]->args as $argIndex => $args ){
                    if( $compare->name === MethodList::Contains->value ){
                      $exprValues = [ $this->resolveWildcardLike(), ...$args, $this->resolveWildcardLike() ];
                    }
                    if( $compare->name === MethodList::StartsWith->value ){
                      $exprValues = [ ...$args, $this->resolveWildcardLike() ];
                    }
                    if( $compare->name === MethodList::EndsWith->value ){
                      $exprValues = [ $this->resolveWildcardLike(), ...$args ];
                    }
                    
                    if((int)$argIndex !== 0){
                      $exprLikes[] = new ExpLogical(
                        LogicalType::Or->value
                      );
                    }                    

                    $exprLikes[] = new ExpLike([ 
                      new ExpField( $child->table, $child->column, $child->type, $modifys ),
                      new ExpValue( $exprValues )
                    ]);
                  }

                  $tokens[ $tokenIndex ] = new ExpGroup( $exprLikes );
                }

                /* Resolver Is In */
                if( $this->resolveIsIn( $compare->name )){
                  $tokens[ $tokenIndex ] = new ExpIn([ 
                    new ExpField( $child->table, $child->column, $child->type, $modifys ), 
                    new ExpValue( $this->resolveEntryParenteses( $compares[ $comporeIndex ]->args ))
                  ]);
                }
                
                /* Resolver Is NotIn */
                if( $this->resolveIsNotIn( $compare->name )){
                  $tokens[ $tokenIndex ] = new ExpNotIn([ 
                    new ExpField( $child->table, $child->column, $child->type, $modifys ), 
                    new ExpValue( $this->resolveEntryParenteses( $compares[ $comporeIndex ]->args ))
                  ]);
                }
              }
            }
          }
        }
      }
    }

    return $tokens;
  }

  private function resolveSemanticsImprovementsInBetween(
    array $tokens = []
  ): array {
    for( $x=0; $x < count($tokens); $x++ ){
      if( $tokens[$x] instanceof ExpCompare ){
        [ $exprFieldA, ,$exprValueA ] = $tokens[$x]->childs;
        
        if( $exprFieldA instanceof ExpField && $exprValueA instanceof ExpValue ){
          for( $y=$x + 1; $y < count($tokens); $y++ ){
            if( $tokens[$y] instanceof ExpCompare ){
              [ $exprFieldB, ,$exprValueB ] = $tokens[$y]->childs;

              if( $exprFieldB instanceof ExpField && $exprValueB instanceof ExpValue ){
                if( $exprFieldA->table === $exprFieldB->table && $exprFieldA->type === $exprFieldB->type ){
                  if( $exprFieldA->column === $exprFieldB->column && $tokens[$y - 1] instanceof ExpLogical && $tokens[$y - 1]->value === LogicalType::And->value ){
                    $tokens[$x] = new ExpBetween([
                      new ExpField(
                        $exprFieldA->table,
                        $exprFieldA->column,
                        $exprFieldA->type, [
                          new ExpFieldMethod(
                            MethodList::ToDate->value, 
                            MethodType::Modify, []
                          )
                        ]
                      ), $exprValueA, $exprValueB 
                    ]);

                    $y !== 0
                      ? array_splice( $tokens, $y - 1, 2 ) 
                      : array_splice( $tokens, $y, 1 );                    
                  }
                }
              }
            }
          }
        }
      }
    }

    return $tokens;
  }

  private function resolveIsExpIn(
    array $tokens = []
  ): bool {
    [ $tokenStart, $tokenEnd ] = [ 
      ...$this->slice( $tokens, 0, 1 ), 
      ...$this->slice( $tokens, -1, 1 )
    ];

    return $tokenStart instanceof ExpToken && $tokenStart->key === T_START_BRACKET
        && $tokenEnd instanceof ExpToken && $tokenEnd->key === T_END_BRACKET;
  }

  private function resolveIsLikeFromWildcard(
    array $tokens = []
  ): bool {
    $tokensText = implode( "", $this->mapper(
      $tokens, fn( ExpToken $expr ) => $expr->value 
    ));

    return preg_match(
      "#(?<!\\\\)%#", $tokensText
    );
  }

  private function resolveIsNullOrNullFromString(
    array $tokens = []
  ): bool {
    if( count( $tokens ) !== 1 ){
      return false;
    }

    [ $token ] = $tokens;
    return strtolower($token->value) === "null";
  }

  private function resolveSemanticsImprovementsInCompare(
    array $tokens = []
  ): array {
    for( $x=0; $x < count($tokens); $x++ ){
      if( $tokens[$x] instanceof ExpCompare ){
        [ $exprField, $exprEqual, $exprValue ] = $tokens[$x]->childs;
        
        if( $exprField instanceof ExpField && $exprValue instanceof ExpValue ){

          /* resolve Improvements to In */
          if( $this->resolveIsExpIn( $exprValue->childs )){
            $tokens[$x] = new ExpIn([
              $exprField, new ExpValue(
                $this->resolveEntryParenteses(
                  $this->slice( $exprValue->childs, 1, -1 )
                )
              )
            ]);
          }
          
          /* resolve Improvements to Between 'find%' */
          if( $this->resolveIsLikeFromWildcard( $exprValue->childs )){
            $tokens[$x] = $exprEqual->value === "="
              ? new ExpLike([ $exprField, new ExpValue( $exprValue->childs )])
              : new ExpNotLike([ $exprField, new ExpValue( $exprValue->childs )]);
          }

          /* resolve Improvements IsNull or Null */
          if( $this->resolveIsNullOrNullFromString( $exprValue->childs )){
            if( $exprEqual instanceof ExpEqual ){
              $tokens[$x] = $exprEqual->value === "="
                ? new ExpIsNull([ $exprField ])
                : new ExpIsNotNull([ $exprField ]);
            }
          }
        }
      }
    }

    return $tokens;
  }  

  private function resolveSemanticsImprovementsInUnary(
    array $tokens = []
  ): array {
    for( $x=0; $x < count($tokens); $x++ ){
      if( $tokens[$x] instanceof ExpUnary ){
        [ $exprField ] = $tokens[$x]->childs;
        
        if( $exprField->type === basename( ColumnFlag::class )){
          $tokens[ $x ] = new ExpCompare([ 
            $exprField, 
              new ExpEqual( "=" ),
                new ExpValue([ $this->resolveEqualTrue() ])
          ]);
        } else if( $exprField->type !== basename( ColumnFlag::class )){
          $tokens[ $x ] = new ExpIsNull([
            $exprField
          ]);
        }
      }
    }

    return $tokens;
  }

  private function resolveSemanticsImprovements(
    array $tokens = []
  ): array {
    $tokens = $this->resolveSemanticsImprovementsInBetween( $tokens );
    $tokens = $this->resolveSemanticsImprovementsInCompare( $tokens );
    $tokens = $this->resolveSemanticsImprovementsInUnary( $tokens );
    return $tokens;
  }  

  private function resolveSemanticsApply(
    array $tokens = []
  ): array {
    $tokens = $this->resolveSemanticsMethods( $tokens );
    $tokens = $this->resolveSemanticsImprovements( $tokens );
    $tokens = $this->resolveSemantics( $tokens );
    return $tokens;
  }

  private function resolveSemantics(
    array $tokens = []
  ): array {
    return $this->mapper(
      $tokens, function( mixed $expr ){
        if( $expr instanceof ExpGroup || $expr instanceof ExpSubQuery || $expr instanceof ExpDenying ){
          $expr->childs = $this->resolveSemanticsApply( $expr->childs );
        }

        return $expr;
      }
    );
  }

  private function resolveWheres(
  ): void {
    $this->tokens = $this->resolveHierarchy( $this->resolveScopesAndTokens());
    $this->tokens = $this->resolveSemantics( $this->tokens );

    $this->scriptDialect = match( Connection::driver()){
      DriverType::MySql => new MySqlScriptDialect( $this->expressionType, $this->statics ),
      DriverType::Sqlite => new SqlLiteScriptDialect( $this->expressionType, $this->statics ),
      DriverType::SqlServer => new SqlServerScriptDialect( $this->expressionType, $this->statics ),
      DriverType::PostgreSQL => new PostgresSqlScriptDialect( $this->expressionType, $this->statics ),
    };

    [ $this->script, $this->params ] = [
      $this->scriptDialect->resolveExprWhere( $this->tokens ),
      $this->scriptDialect->resolveExprParams()
    ];    
  }

  private function resolveClear(
  ): void {
    unset( $this->expression );
  }

  public function getWhereResult(
  ): WhereResult {
    $this->resolveExpressionType();
    $this->resolveReflection();
    $this->resolveRows();
    $this->resolveUses();
    $this->resolveStatics();
    $this->resolveExpression();
    $this->resolveWheres();
    $this->resolveClear();

    return new WhereResult(
      $this->script, $this->params
    );
  }

  private function hierackyTypeColumnField(
    array $tokens = []
  ): bool {
    return $this->isField( $tokens );
  }  

  private function hierackyTypeColumnOperator(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_MULTIPLY, T_PLUS, T_MINUS, T_DIVIDE
    ]);
  }

  private function hierackyTypeColumnMethod(
    array $tokens = []
  ): bool {
    if( count( $tokens ) < 3 ){
      return false;
    }

    if( in_array( $tokens[2]->value, [ MethodList::Sum->value ]) === false){
      return false;
    }

    return $tokens[0]->key === T_VARIABLE
        && $tokens[1]->key === T_OBJECT_OPERATOR
        && $tokens[2]->key === T_STRING
        && $tokens[3]->key === T_START_PARENTESES;
  }

  private function hierackyTypeColumnSeparator(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_COMMA
    ]);
  }   

  private function hierackyColumnType(
    array $tokens = []
  ): string|null {
    if( $this->hierackyTypeGroup( $tokens )){
      return ExpGroup::class;
    } else 
    if( $this->hierackyTypeColumnMethod( $tokens )){
      return ExpMethod::class;
    } else 
    if( $this->hierackyTypeColumnSeparator( $tokens )){
      return ExpSeparator::class;
    } else
    if( $this->hierackyTypeColumnField( $tokens )){
      return ExpField::class;
    } else
    if( $this->hierackyTypeColumnOperator( $tokens )){
      return ExpOperator::class;
    } 
      
    return null;    
  }

  private function resolveHierarchyToExpColumnGroup(
    array $childs = []
  ): ExpGroup {
    return new ExpGroup(
      $this->resolveHierarchyColumn( 
        $this->slice( $childs, 1, -1 )
      )
    );
  }

  private function resolveHierarchyToExpColumnField(
    array $childs = []
  ): ExpField {
    return $this->resolveField( $childs );
  }  
  
  private function resolveHierarchyToExpColumnMethod(
    array $childs = []
  ): ExpMethod {
    return new ExpMethod(
      $childs[2]->value, $this->resolveHierarchyColumn(
        $this->slice( $childs, $this->indexOf( $childs, T_START_PARENTESES ) + 1, -1 )
      )
    );
  }  

  private function resolveHierarchyToExpColumnSeparator(
    array $childs = []
  ): ExpSeparator {
    return new ExpSeparator(
      $childs[0]->value
    );
  }
  
  private function resolveHierarchyToExpColumnOperator(
    array $childs = []
  ): ExpOperator {
    return new ExpOperator(
      $childs[0]->value
    );
  }  
  
  private function resolveHierarchyColumn(
    array $tokens = []
  ): array {
    return $this->mapper(
      $this->groupByTypesColumn( $tokens ), fn( array $childs ) =>
        match( $this->hierackyColumnType( $childs )){
          ExpGroup::class => $this->resolveHierarchyToExpColumnGroup( $childs ),
          ExpField::class => $this->resolveHierarchyToExpColumnField( $childs ),
          ExpMethod::class => $this->resolveHierarchyToExpColumnMethod( $childs ),
          ExpSeparator::class => $this->resolveHierarchyToExpColumnSeparator( $childs ),
          ExpOperator::class => $this->resolveHierarchyToExpColumnOperator( $childs ),
            default => $childs
        }
    );
  }  

  private function resolveColumns(
  ): void {
    $this->tokens = $this->resolveHierarchyColumn(
      $this->resolveScopesAndTokens()
    );

    $this->scriptDialect = match( Connection::driver()){
      DriverType::MySql => new MySqlScriptDialect( $this->expressionType, $this->statics ),
      DriverType::Sqlite => new SqlLiteScriptDialect( $this->expressionType, $this->statics ),
      DriverType::SqlServer => new SqlServerScriptDialect( $this->expressionType, $this->statics ),
      DriverType::PostgreSQL => new PostgresSqlScriptDialect( $this->expressionType, $this->statics ),
    };

    [ $this->script, $this->params ] = [
      $this->scriptDialect->resolveExprColumns( $this->tokens ),
      $this->scriptDialect->resolveExprParams()
    ];    
  }  

  public function getColumnResult(
  ): ColumnResult {
    $this->resolveExpressionType();
    $this->resolveReflection();
    $this->resolveRows();
    $this->resolveUses();
    $this->resolveStatics();
    $this->resolveExpression();
    $this->resolveColumns();
    $this->resolveClear();

    return new ColumnResult(
      $this->script, $this->params
    );
  }
}