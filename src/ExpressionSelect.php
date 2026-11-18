<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpGroup;
use Websyspro\ArrowToSql\Expressions\ExpMethod;
use Websyspro\ArrowToSql\Expressions\ExpOperator;
use Websyspro\ArrowToSql\Expressions\ExpSelectColumn;
use Websyspro\ArrowToSql\Expressions\ExpSelectCount;
use Websyspro\ArrowToSql\Expressions\ExpSelectSubQuery;
use Websyspro\ArrowToSql\Expressions\ExpSelectSum;
use Websyspro\ArrowToSql\Expressions\ExpSeparator;
use Websyspro\Entity\Decorators\EntityList;

use function is_array;
use function sprintf;
use function sizeof;

class ExpressionSelect
extends ParserUtils
{
  public array $parserScript = [];

  public AbstractScriptDialect $abstractScriptDialect;

  public function __construct(
    public Parser $parser,
    public Repository $repository
  ){
    parent::__construct( 
      $this->parser,
      $this->repository
    );

    $this->setHierarchyColumns();
    $this->setClearVariables();
  }

  private function table(
  ): string {
    return $this->repository->getEntityStructure()->getEntityAlias();
  }

  private function getHierackyColumnType(
    array $tokens = []
  ): string|null {
    if( ParserUtils::isTypeGroup( $tokens )){
      return ExpGroup::class;
    } else 
    if( ParserUtils::isTypeColumnMethod( $tokens )){
      return ExpMethod::class;
    } else 
    if( ParserUtils::isTypeColumnSeparator( $tokens )){
      return ExpSeparator::class;
    } else
    if( ParserUtils::isTypeColumnField( $tokens )){
      return ExpField::class;
    } else
    if( ParserUtils::isTypeColumnOperator( $tokens )){
      return ExpOperator::class;
    } 
      
    return null;    
  }
  
  private function resolveHierarchyToExpColumnGroup(
    array $tokens = []
  ): ExpGroup {
    return new ExpGroup(
      $this->resolveHierarchyColumns( 
        $this->slice( $tokens, 1, -1 )
      )
    );
  }

  private function resolveHierarchyToExpColumnField(
    array $tokens = []
  ): ExpField {
    return $this->resolveField( $tokens );
  } 
  
  private function descriptor(
    array $expr = []
  ): string {
    [ $_, $_, $descriptor ] = $expr;
    return MethodList::from( $descriptor->value )->name;
  }

  private function closure(
    array $expr = []
  ): array {
    return $this->mapper(
      $this->groupByTypes(
        [ T_OBJECT_OPERATOR ], $this->slice(
          $expr, sizeof(
            $this->contextsNotEnds(
              $this->slice( $expr, 4 )
            )
          ) + 6
        )
      ), fn( array $method ) => (
        new ExpFieldMethod(
          $method[0]->value, 
          $this->resolveFieldMethodType( $method[0]->value ),
          $this->slice( $method, 2, -1 )
        )
      )
    );
  }

  private function childs(
    array $expr = []
  ): array {
    return $this->resolveHierarchyColumns(
      $this->contextsNotEnds( $this->slice( 
        $expr, $this->indexOf( $expr, T_START_PARENTESES ) + 1, -1
      ))
    );
  }

  private function resolveHierarchyToExpColumnMethod(
    array $expr = []
  ): ExpMethod {
    return new ExpMethod(
      table: $this->table(), 
      descriptor: $this->descriptor( $expr ),
      closures: $this->closure( $expr ),
      childs: $this->childs( $expr )
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
  
  private function resolveHierarchyColumns(
    array $tokens = []
  ): array {
    return $this->mapper(
      $this->groupByTypesColumn( $tokens ), fn( array $childs ) =>
        match( $this->getHierackyColumnType( $childs )){
          ExpGroup::class => $this->resolveHierarchyToExpColumnGroup( $childs ),
          ExpField::class => $this->resolveHierarchyToExpColumnField( $childs ),
          ExpMethod::class => $this->resolveHierarchyToExpColumnMethod( $childs ),
          ExpSeparator::class => $this->resolveHierarchyToExpColumnSeparator( $childs ),
          ExpOperator::class => $this->resolveHierarchyToExpColumnOperator( $childs ),
            default => $childs
        }
    );
  }

  private function resolveExprColumnGroup(
    ExpGroup $expr,
    bool $depth = false
  ): ExpGroup {
    return $expr;
  }

  private function firstExprField( 
    array $expressions
  ): ExpField|null {
    foreach( $expressions as $expression ){
      if( $expression instanceof ExpField ){
        return $expression;
      }
      if( $expression instanceof ExpGroup || $expression instanceof ExpMethod ){
        $field = $this->firstExprField( $expression->childs );
        if( $field !== null ) return $field;
      }
    }

    return null;
  }  

  private function resolverExpSelectColumn(
    string $table,
    string $column,
    string $columnAs
  ): ExpSelectColumn {
    return new ExpSelectColumn(
      $table, $column, $columnAs
    );
  }

  public function resolveColumnAs(
    ExpField|ExpMethod $expr
  ): string|null {
    $aliasOfClosures = $this->filterMethodsArr(
      $expr, [ MethodType::Alias ]
    );

    if( empty( $aliasOfClosures )){
      return $expr instanceof ExpField 
        ? $expr->column 
        : $expr->descriptor;
    }

    [ $alais ] = $aliasOfClosures;
    return $alais->args[0]->value;
  }
  
  public function resolveExprField(
    ExpField $expr,
    bool $depth = false
  ): ExpSelectColumn {
    if( $expr instanceof ExpField ){
      $methods = $depth === false 
        ? $this->filterMethodsArr($expr) 
        : [];

      $column = "{$expr->table}.{$expr->column}";

      foreach( $methods as $method ){
        if( $method instanceof ExpFieldMethod ){
          $column = $this->getExprFieldMethods(
            $column, $method
          );
        }
      }

      return $this->resolverExpSelectColumn(
        $expr->table, 
        $expr->column, 
        $this->resolveColumnAs( $expr ) 
      );      
    }
    
    return $this->resolverExpSelectColumn( "", "", "" );
  }
  
  private function isMethodSubQuery(
    ExpField $expr
  ): bool {
    return $expr->columnType === EntityList::class && !empty( $expr->closures );
  }

  private function resolveTokensFromMethodSelect(
    ExpField $expr
  ): array {
    $methods = $this->filterMethodsArr(
      $expr, [ MethodType::Modify ] 
    );

    if( empty( $methods )){
      return [];
    }

    [ $method ] = $methods;
    return $method instanceof ExpFieldMethod
      ? $method->args : [];
  }

  private function resolveSubQuery(
    ExpField $expr
  ): ExpSelectSubQuery|null {
    if( $expr->columnType === EntityList::class ){
      $repositoryInRuntime = new RepositoryInRuntime(
        $this->repository->getEntityStructure()->entityLists->items[ $expr->column ],
        $this->parser->uses, $this->parser->scopes, $this->parser->getClosure()
      );

      $repositoryInRuntime->setWheres( $this->resolveTokensFromMethodSelect( $expr ));
      $repositoryInRuntime->setSelect( $this->resolveTokensFromMethodSelect( $expr ));

      return new ExpSelectSubQuery(
        $expr->table,
        $expr->column,
        $this->resolveColumnAs( $expr ),
        $expr->table,
        $repositoryInRuntime
      );      
    }

    return null;
  }
  
  private function resolveExprColumnField(
    ExpField $expr,
    bool $depth = false
  ): ExpSelectColumn|ExpSelectSubQuery {
    if( $this->isMethodSubQuery( $expr )){
      return $this->resolveSubQuery( $expr );
    } else return $this->resolveExprField( $expr, $depth );
  }
  
  private function resolveColumnFragment( 
    string|array $fragment
  ): string {
    if( is_array( $fragment )){
      return $this->resolveColumnFragment( $fragment[0] ?? "" );
    }

    return $fragment;
  } 
  
  private function resolveExprColumnMethod(
    ExpMethod $expr
  ): ExpSelectSum|ExpSelectCount|null {
    return match( $expr->descriptor ){
      MethodList::Sum->name => new ExpSelectSum( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs )),
      MethodList::Count->name => new ExpSelectCount( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs )),
        default => null
    };
  } 
  
  private function resolveExprColumnSeparator(
    ExpSeparator $expr
  ): ExpSeparator {
    return $expr;
  }
  
  private function resolveExprColumnOperator(
    ExpOperator $expr
  ): ExpOperator {
    return $expr;
  }  

  public function resolveExprColumns(
    array $tokens = [],
    bool $depth = false
  ): array {
    $tokens = $this->mapper(
      $tokens, function( mixed $expr ) use( $depth ) {
        if( $expr instanceof ExpGroup ){
          return $this->resolveExprColumnGroup( $expr, $depth );
        } else
        if( $expr instanceof ExpField ){
          return $this->resolveExprColumnField( $expr, $depth );
        } else
        if( $expr instanceof ExpMethod ){
          return $this->resolveExprColumnMethod( $expr, $depth );
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

    return $tokens;
  }  

  private function setHierarchyColumns(
  ): void {
    $this->parser->tokens = $this->resolveHierarchyColumns( $this->parser->tokens );
    $this->parserScript = $this->resolveExprColumns( $this->parser->tokens );
  }

  private function setClearVariables(
  ): void {
    unset( $this->repository, $this->abstractScriptDialect );
    unset( $this->parser );
  }  
}