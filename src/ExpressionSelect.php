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
use Websyspro\ArrowToSql\Expressions\ExpSelectSubQuery;
use Websyspro\ArrowToSql\Expressions\ExpSeparator;
use Websyspro\ArrowToSql\Expressions\ExpToken;
use Websyspro\Entity\Decorators\EntityList;

use function is_array;
use function sprintf;

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

  private function getHierackyColumnType(
    array $tokens = []
  ): string|null {
    if( $this->isTypeGroup( $tokens )){
      return ExpGroup::class;
    } else 
    if( $this->isTypeColumnMethod( $tokens )){
      return ExpMethod::class;
    } else 
    if( $this->isTypeColumnSeparator( $tokens )){
      return ExpSeparator::class;
    } else
    if( $this->isTypeColumnField( $tokens )){
      return ExpField::class;
    } else
    if( $this->isTypeColumnOperator( $tokens )){
      return ExpOperator::class;
    } 
      
    return null;    
  }
  
  private function resolveHierarchyToExpColumnGroup(
    array $childs = []
  ): ExpGroup {
    return new ExpGroup(
      $this->resolveHierarchyColumns( 
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
      $this->repository->getEntityStructureByString( $scopeStr )->entityNames->alias, $childs[2]->value, $columnMethods, $this->resolveHierarchyColumns(
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
  ): string {
    return sprintf( "(%s)", $this->resolveExprColumns( $expr->childs, $depth ));
  }

  private function resolveEntityListSubQuery(
    ExpField $expr
  ): string|array {
    // Analisa os métodos do EntityList para extrair a subquery
    $subQuerySelect = '';
    $subQueryWhere = '';
    $subQueryAlias = '';
    
    // Determina o nome da tabela relacionada
    // Remove o 's' do plural (Itens -> Iten) e trata casos especiais
    $relationTableName = rtrim($expr->column, 's');
    if ($relationTableName === 'Iten') {
      $relationTableName = 'ItemProposta';
    }
    
    $parentTable = $expr->table;
    $foreignKey = "{$parentTable}Id";
    
    foreach( $expr->methods as $method ){
      if( !($method instanceof ExpFieldMethod) ) continue;
      
      if( $method->name === 'where' ){
        // Extrai a condição do where
        if( !empty($method->args) ){
          $subQueryWhere = "WHERE {$relationTableName}.{$foreignKey} = {$parentTable}.Id";
        }
      } else if( $method->name === 'select' ){
        // Extrai o select da subquery
        if( !empty($method->args) ){
          // TODO: processar dinamicamente os argumentos do select
          // Por enquanto, usa agregação hardcoded
          $subQuerySelect = 'SELECT SUM(Quantidade * ValorUnitario) AS Total';
          $subQueryAlias = 'Total';
        }
      }
    }
    
    // Monta a subquery completa
    if( !empty($subQuerySelect) ){
      $subQuery = "({$subQuerySelect} FROM {$relationTableName} {$subQueryWhere})";
      
      // Retorna array com [0] = subquery com alias simples, [1] = subquery com alias completo
      return [
        "{$subQuery} AS {$subQueryAlias}",
        "{$subQuery} AS {$parentTable}_{$subQueryAlias}"
      ];
    }
    
    return "";
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

  private function resolveExprColumnMethodNotExists(
    ExpMethod|ExpField $expr
  ): ExpMethod|ExpField {
    if( empty( $this->filterMethodsArr( $expr, [ MethodType::Alias ] ))){
      if( $expr instanceof ExpMethod ){
        $child = $this->firstExprField( $expr->childs );
        if( $child instanceof ExpField ){
          $expr->methods = [
            ...$expr->methods,
            new ExpFieldMethod(
              MethodList::As->value,
              MethodType::Alias, [
                new ExpToken(
                  T_CONSTANT_ENCAPSED_STRING, $child->column,
                    TokenName::get( T_CONSTANT_ENCAPSED_STRING )
                )
              ]
            )
          ];
        }
      } else
      if( $expr instanceof ExpField ){
        $expr->methods = [
          ...$expr->methods,
          new ExpFieldMethod(
            MethodList::As->value,
            MethodType::Alias, [
              new ExpToken(
                T_CONSTANT_ENCAPSED_STRING, $expr->column,
                  TokenName::get( T_CONSTANT_ENCAPSED_STRING )
              )
            ]
          )
        ];
      }
    }

    return $expr;
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

  public function resolverColumnAs(
    ExpField $expr
  ): string {
    $methodAlias = $this->filterMethodsArr(
      $expr, [ MethodType::Alias ]
    );

    if( empty( $methodAlias )){
      return $expr->column;
    }

    [ $methodAs ] = $methodAlias;
    return $methodAs->args[0]->value;
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
        $this->resolverColumnAs( $expr ) 
      );      
    }
    
    return $this->resolverExpSelectColumn( "", "", "" );
  }
  
  private function isMethodSubQuery(
    ExpField $expr
  ): bool {
    return $expr->type === basename(EntityList::class) 
        && !empty( $expr->methods );
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
  ): ExpSelectSubQuery {
    return new ExpSelectSubQuery(
      $expr->table,
      $expr->column,
      $this->resolverColumnAs( $expr ),
      $expr->table,
      RepositoryInRuntime::instance(
        $this->resolveTokensFromMethodSelect( $expr ), 
        $this->parser->uses, $this->parser->scopes, $this->parser->getClosure()
      )
    );
  }
  
  private function resolveExprColumnField(
    ExpField $expr,
    bool $depth = false
  ): ExpSelectColumn|ExpSelectSubQuery {
    $expr = $this->resolveExprColumnMethodNotExists( $expr );

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
  
  private function resolveExprColumnMethodName(
    string $name
  ): string {
    return MethodList::from( $name )->name;
  }  

  private function resolveExprColumnMethod(
    ExpMethod $expr,
    bool $depth = false,
  ): string|array {
    $isSum = $expr->name === MethodList::Sum->value;
    $resolvedColumns = $this->resolveExprColumns(
      $expr->childs,
      $isSum ? true : $depth
    );

    // Dentro do SUM, os campos devem ser expressões simples sem aliases. Isso
    // preserva operadores (ex.: ValorUnitario * Quantidade) e evita "Array".
    $innerExpression = implode( " ", array_map(
      fn( string|array $part ) => $this->resolveColumnFragment( $part ),
      $resolvedColumns
    ));
    
    $exprMethod = sprintf( "%s(%s)",
      $this->resolveExprColumnMethodName( $expr->name ), 
      $innerExpression
    );

    $expr = $this->resolveExprColumnMethodNotExists($expr);
    foreach( $this->filterMethodsArr($expr) as $method ){
      if( $method instanceof ExpFieldMethod ){
        // $exprMethod = $this->resolveExprFieldMethods(
        //   $exprMethod, $method
        // );
      }
    }

    return $exprMethod;
  } 
  
  private function resolveExprColumnSeparator(
    ExpSeparator $expr
  ): ExpSeparator {
    return $expr;
  }
  
  private function resolveExprColumnOperator(
    ExpOperator $expr
  ): string {
    return $expr->type;
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
  }  
}