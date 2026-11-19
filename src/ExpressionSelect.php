<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\AbstractExpTypeSelect;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpGroup;
use Websyspro\ArrowToSql\Expressions\ExpMethod;
use Websyspro\ArrowToSql\Expressions\ExpOperator;
use Websyspro\ArrowToSql\Expressions\ExpSelectAvg;
use Websyspro\ArrowToSql\Expressions\ExpSelectColumn;
use Websyspro\ArrowToSql\Expressions\ExpSelectCount;
use Websyspro\ArrowToSql\Expressions\ExpSelectMax;
use Websyspro\ArrowToSql\Expressions\ExpSelectMin;
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
    array $closures = []
  ): ExpSelectColumn {
    return new ExpSelectColumn(
      $table, $column, $closures
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
        $expr->table, $expr->column, $expr->closures,
      );      
    }
    
    return $this->resolverExpSelectColumn( "", "", "" );
  }
  
  private function isMethodSubQuery(
    ExpField $expr
  ): bool {
    return $expr->columnType === EntityList::class && !empty( $expr->closures );
  }

  private function createAstSubQuery(
    ExpField $expr
  ): ExpSelectSubQuery|null {
    if( $this->isMethodSubQuery( $expr ) ){
      $repositoryInRuntime = new RepositoryInRuntime(
        $this->repository->getEntityStructure()->entityLists->items[ $expr->column ],
        $this->parser->uses, $this->parser->scopes, $this->parser->getClosure()
      );

      $closuresExtras = ParserUtils::where(
        $expr->closures, fn( ExpFieldMethod $expFieldMethod ) => in_array(
          $expFieldMethod->descriptor, [ MethodList::Select->value, MethodList::Where->value ]
        ) === false
      );      

      $scopeWheres = ParserUtils::where(
        $expr->closures, fn( ExpFieldMethod $expFieldMethod ) => (
          $expFieldMethod->descriptor === MethodList::Where->value
        )
      );

      if( empty( $scopeWheres ) === false ){
        [ $scopeWhere ] = $scopeWheres;
        $repositoryInRuntime->setWheres( $scopeWhere->args );
      }

      $scopeSelects = ParserUtils::where(
        $expr->closures, fn( ExpFieldMethod $expFieldMethod ) => (
          $expFieldMethod->descriptor === MethodList::Select->value
        )
      );

      if( empty( $scopeSelects ) === false ){
        [ $scopeSelect ] = $scopeSelects;
        $repositoryInRuntime->setSelect( $scopeSelect->args );
      }

      return new ExpSelectSubQuery( $closuresExtras, $repositoryInRuntime );      
    } else return null;
  }
  
  private function resolveExprColumnField(
    ExpField $expr,
    bool $depth = false
  ): ExpSelectColumn|ExpSelectSubQuery {
    if( $this->isMethodSubQuery( $expr )){
      return $this->createAstSubQuery( $expr );
    } else return $this->resolveExprField( $expr, $depth );
  }
  
  private function resolveExprColumnMethod(
    ExpMethod $expr
  ): AbstractExpTypeSelect|null {
    if( $expr->descriptor === MethodList::Sum->name ){
      return new ExpSelectSum( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs ));
    } else
    if( $expr->descriptor === MethodList::Avg->name ){
      return new ExpSelectAvg( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs ));
    } else
    if( $expr->descriptor === MethodList::Count->name ){
      return new ExpSelectCount( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs ));
    } else 
    if( $expr->descriptor === MethodList::Max->name ){
      return new ExpSelectMax( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs ));
    } else 
    if( $expr->descriptor === MethodList::Min->name ){
      return new ExpSelectMin( $expr->table, $expr->descriptor, $this->resolveExprColumns( $expr->childs ));
    } else return null;
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