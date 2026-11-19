<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\parser;
use Websyspro\ArrowToSql\Repository;
use Websyspro\ArrowToSql\Enums\LogicalType;
use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
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
use Websyspro\Entity\Types\ColumnFlag;
use function is_array;
use function in_array;
use function sprintf;
use function sizeof;

class ExpressionWhere
extends ParserUtils
{
  public array $parserScript = [];
  public array $parserParams = [];  

  public function __construct(
    public Parser $parser,
    public Repository $repository
  ){
    parent::__construct( 
      $this->parser,
      $this->repository
    );

    $this->setHierarchyColumns();
    $this->setSemanticsColumns();
    $this->setClearVariables();
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

  private function hierackyTypeSubQuery(
    array $tokens = []
  ): bool {
    return in_array( $this->resolveSubQueryMethod( $tokens ), [ "any" ]);
  }  

  private function hierackyTypeCompare(
    array $tokens = []
  ): bool {
    foreach( $tokens as $token ){
      if( in_array( $token->key, [ 
        T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL,
        T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL,
        T_LESS_THAN, T_GREATER_THAN, T_EQUAL
      ])){
        return true;
      }
    }

    return false;
  }

  private function hierackyTypeUnary(
    array $tokens = []
  ): bool {
    if( $this->isField( $tokens ) === false ){
      return false;
    }

    return $this->hierackyTypeCompare($tokens) === false;
  }

  private function hierackyTypeLogical(
    array $tokens = []
  ): bool {
    return in_array( $tokens[0]->key, [
      T_LOGICAL_AND, T_LOGICAL_OR, T_BOOLEAN_AND, T_BOOLEAN_OR
    ]);
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



  private function createASTExpDenying(
    array $childs = [],
  ): ExpDenying {
    return new ExpDenying(
      $this->resolveHierarchy( 
        $this->slice( $childs, 1 )
      )
    );
  }  

  private function createASTExpGroup(
    array $childs = []
  ): ExpGroup {
    return new ExpGroup(
      $this->resolveHierarchy( 
        $this->slice( $childs, 1, -1 )
      )
    );
  }

  private function createASTExpSubQuery(
   array $tokens = []
  ): ExpSubQuery {
    $scopeName = ExpressionWhereUtils::extractScopeName( $tokens );  
    $scopeWheres = ExpressionWhereUtils::extractScopeWhere( $tokens );

    return new ExpSubQuery(
      $scopeName, ExpressionWhereUtils::createRepositoryInRunTime( 
        $scopeWheres, $this->parser->uses, $this->parser->scopes, $this->parser->getClosure()
      )
    );
  }  

  private function createASTExpLogical(
    array $childs = []
  ): ExpLogical {
    return new ExpLogical(
      match( $childs[0]->key ){
        T_LOGICAL_AND, T_BOOLEAN_AND => LogicalType::And->value,
        T_LOGICAL_OR, T_BOOLEAN_OR => LogicalType::Or->value
      }
    );
  }

  private function createASTExpCompare(
    array $tokens = []
  ): ExpCompare {
    [ $astCompareLeft, $astCompareEqual, $astCompareRight 
    ] = ExpressionWhereUtils::groupByTypesFromCompare( $tokens );

    $astCompareLeft = sizeof( $astCompareLeft ) === 1 && ParserUtils::isField( $astCompareLeft, $this->repository->getEntityStructure()->columns->items )
      ? ExpressionWhereUtils::createASTExpField( $astCompareLeft, $this->parser, $this->repository )
      : (
        sizeof( $astCompareLeft ) === 1 && $astCompareLeft[0]->key === T_VARIABLE
          ? $this->resolveValue( $astCompareLeft )
          : (
            is_array( $this->resolveHierarchyColumn( $astCompareLeft )) && sizeof($this->resolveHierarchyColumn( $astCompareLeft )) === 1
              ? $this->resolveHierarchyColumn( $astCompareLeft )[0]
              : $this->resolveHierarchyColumn( $astCompareLeft )
          )
      );

    $astCompareRight = ParserUtils::isField( $astCompareRight, $this->repository->getEntityStructure()->columns->items )
      ? ExpressionWhereUtils::createASTExpField( $astCompareRight, $this->parser, $this->repository ) 
      : ExpressionWhereUtils::createASTExpValue( $astCompareRight, $astCompareLeft->columnType, $this->parser );

    $astCompareExpEqual = ExpressionWhereUtils::createASTExpEqual( $astCompareEqual );
    if( $astCompareLeft instanceof ExpValue ){
      return new ExpCompare([ $astCompareRight, $astCompareExpEqual->invert(), $astCompareLeft ]);
    }

    return new ExpCompare([ $astCompareLeft, $astCompareExpEqual, $astCompareRight ]);
  }

  private function createASTExpUnary(
    array $childs = []
  ): ExpUnary {
    return new ExpUnary([
      $this->resolveField( $childs )
    ]);
  }

  private function resolveHierarchy(
    array $tokens = []
  ): array {
    $tokens = $this->mapper(
      $this->groupByTypesLogical( $tokens ), fn( array $childs ) =>
        match( $this->hierackyType( $childs )){
          ExpDenying::class => $this->createASTExpDenying( $childs ),
          ExpGroup::class => $this->createASTExpGroup( $childs ),
          ExpSubQuery::class => $this->createASTExpSubQuery( $childs ),
          ExpLogical::class => $this->createASTExpLogical( $childs ),
          ExpCompare::class => $this->createASTExpCompare( $childs ),
          ExpUnary::class => $this->createASTExpUnary( $childs ),
            default => $childs
        }
    );

    return $tokens;
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
    $columnMethods = $this->mapper(
      $this->groupByTypes(
        [ T_OBJECT_OPERATOR ], $this->slice(
          $childs, sizeof(
            $this->contextsNotEnds(
              $this->slice( $childs, 4 )
            )
          ) + 6
        )
      ), fn( array $methods ) => (
        new ExpFieldMethod(
          $methods[0]->value, 
          $this->resolveFieldMethodType( $methods[0]->value ),
          $this->slice( $methods, 2, -1 )
        )
      )
    );

    [ $scopeStr ] = array_values(
      $this->parser->scopes
    );

    return new ExpMethod(
      $this->repository->getEntityStructureByString( $scopeStr )->entityNames->alias, $childs[2]->value, $columnMethods, $this->resolveHierarchyColumn(
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
    array $token = []
  ): array {
    $tokens = $this->mapper(
      $this->groupByTypesColumn( $token ), fn( array $childs ) =>
        match( $this->hierackyColumnType( $childs )){
          ExpGroup::class => $this->resolveHierarchyToExpColumnGroup( $childs ),
          ExpField::class => $this->resolveHierarchyToExpColumnField( $childs ),
          ExpMethod::class => $this->resolveHierarchyToExpColumnMethod( $childs ),
          ExpSeparator::class => $this->resolveHierarchyToExpColumnSeparator( $childs ),
          ExpOperator::class => $this->resolveHierarchyToExpColumnOperator( $childs ),
            default => $childs
        }
    );

    return $tokens;
  }

  private function setHierarchyColumns(
  ): void {
    $this->parserScript = $this->resolveHierarchy( 
      $this->parser->tokens
    );
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

  private function resolveIsEquals(
    string $methdoName
  ): bool {
    return $methdoName === MethodList::Equals->value;
  }  

  private function resolveWildcardLike(
  ): ExpToken {
    return new ExpToken(
      37, "%", TokenName::get(
        T_STRING
      )
    );
  }  

  private function resolveStartParentese(
  ): ExpToken {
    return new ExpToken(40, "(", TokenName::get(T_START_PARENTESES));
  }  

  private function resolveEndParentese(
  ): ExpToken {
    return new ExpToken(41, ")", TokenName::get(T_END_PARENTESES));
  }

  private function resolveEntryParenteses(
    array $tokens = []
  ): array {
    return [ 
      $this->resolveStartParentese(), ...$tokens,
      $this->resolveEndParentese()
    ];
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
                      ExpressionWhereUtils::createASTExpValue( $expTokenBetweenFrom, $child->type, $this->parser ), 
                      ExpressionWhereUtils::createASTExpValue( $expTokenBetweenTo, $child->type, $this->parser )
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
                      ExpressionWhereUtils::createASTExpValue( $exprValues, "", $this->parser )
                    ]);
                  }

                  $tokens[ $tokenIndex ] = new ExpGroup( $exprLikes );
                }

                /* Resolver Is In */
                if( $this->resolveIsIn( $compare->name )){
                  $tokens[ $tokenIndex ] = new ExpIn([ 
                    new ExpField( $child->table, $child->column, $child->type, $modifys ), 
                    ExpressionWhereUtils::createASTExpValue( 
                      $this->resolveEntryParenteses( 
                        $compares[ $comporeIndex ]->args
                      ), "", $this->parser
                    )
                  ]);
                }
                
                /* Resolver Is NotIn */
                if( $this->resolveIsNotIn( $compare->name )){
                  $tokens[ $tokenIndex ] = new ExpNotIn([ 
                    new ExpField( $child->table, $child->column, $child->type, $modifys ), 
                    ExpressionWhereUtils::createASTExpValue( 
                      $this->resolveEntryParenteses( 
                        $compares[ $comporeIndex ]->args
                      ), $child->type, $this->parser
                    )
                  ]);
                }

                /* Resolver Is Equals */
                if( $this->resolveIsEquals( $compare->name )){
                  $tokens[ $tokenIndex ] = new ExpCompare([
                    new ExpField( $child->table, $child->column, $child->type, $modifys ),
                    new ExpEqual( "=" ), ExpressionWhereUtils::createASTExpValue( 
                      $compares[ $comporeIndex ]->args, $child->type, $this->parser
                    )
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
                if( $exprFieldA->table === $exprFieldB->table && $exprFieldA->columnType === $exprFieldB->columnType ){
                  if( $exprFieldA->column === $exprFieldB->column && $tokens[$y - 1] instanceof ExpLogical && $tokens[$y - 1]->value === LogicalType::And->value ){
                    $tokens[$x] = new ExpBetween([
                      new ExpField(
                        $exprFieldA->table,
                        $exprFieldA->column,
                        $exprFieldA->columnType, [
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
    if( sizeof( $tokens ) !== 1 ){
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
              $exprField, ExpressionWhereUtils::createASTExpValue(
                $this->resolveEntryParenteses(
                  $this->slice( $exprValue->childs, 1, -1 )
                ), $exprField->columnType, $this->parser
              )
            ]);
          }
          
          /* resolve Improvements to Between 'find%' */
          if( $this->resolveIsLikeFromWildcard( $exprValue->childs )){
            $tokens[$x] = $exprEqual->value === "="
              ? new ExpLike([ $exprField, ExpressionWhereUtils::createASTExpValue( $exprValue->childs, $exprField->columnType, $this->parser )])
              : new ExpNotLike([ $exprField, ExpressionWhereUtils::createASTExpValue( $exprValue->childs, $exprField->columnType, $this->parser )]);
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

  private function resolveEqualTrue(
  ): ExpToken {
    return new ExpToken(
      313, "true", TokenName::get(
        T_STRING
      )
    );
  }  

  private function resolveSemanticsImprovementsInUnary(
    array $tokens = []
  ): array {
    for( $x=0; $x < sizeof($tokens); $x++ ){
      if( $tokens[$x] instanceof ExpUnary ){
        [ $exprField ] = $tokens[$x]->childs;
        
        if( $exprField->type === basename( ColumnFlag::class )){
          $tokens[ $x ] = new ExpCompare([ 
            $exprField, new ExpEqual( "=" ), ExpressionWhereUtils::createASTExpValue(
              [ $this->resolveEqualTrue() ], $exprField->columnType, $this->parser
            )
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

  private function resolveSemantics(
    array $tokens = []
  ): array {
    $tokens = $this->mapper(
      $tokens, function( mixed $expr ){
        if( $expr instanceof ExpGroup || $expr instanceof ExpSubQuery || $expr instanceof ExpDenying ){
          $expr->childs = $this->setSemanticsColumnApply( $expr->childs );
        }

        return $expr;
      }
    );

    return $tokens;
  }  
  
  private function setSemanticsColumnApply(
    array $tokens = []
  ): array {
    $tokens = $this->resolveSemanticsMethods( $tokens );
    $tokens = $this->resolveSemanticsImprovements( $tokens );
    $tokens = $this->resolveSemantics( $tokens );
    return $tokens;
  }

  private function setSemanticsColumns(
  ): void {
    $this->parser->tokens = $this->setSemanticsColumnApply( $this->parser->tokens );
  }

  public function resolveExprFieldMethodArr(
    ExpField|ExpMethod $expr
  ): array {
    return $this->filter(
      $expr->methods, fn( ExpFieldMethod $expr ) => (
        in_array( $expr->type, [ 
          MethodType::Modify, 
          MethodType::Alias 
        ])
      )
    );    
  }  

  public function resolveExprField(
    ExpField $expr,
    bool $depth = false
  ): string|array {
    if( $expr instanceof ExpField ){
      $methods = $depth === false 
        ? $this->filterMethodsArr($expr) 
        : [];

      $column = "{$expr->table}.{$expr->column}";
      foreach( $methods as $method ){
        if( $method instanceof ExpFieldMethod ){
          $column = $this->getExprFieldMethods( $column, $method );
        }
      }

      return $column;      
    } else return "table.field";
  }  

  private function setClearVariables(
  ): void {
    unset( $this->repository, $this->abstractScriptDialect );
    unset( $this->parser );
  }  
}