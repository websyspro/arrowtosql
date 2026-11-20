<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\MethodList;
use Websyspro\ArrowToSql\Enums\MethodType;
use Websyspro\ArrowToSql\Expressions\ExpCaseElse;
use Websyspro\ArrowToSql\Expressions\ExpCaseWhen;
use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldCaseMethod;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\ArrowToSql\Expressions\ExpToken;
use function in_array;
use function sizeof;

class ExpressionSelectUtils
{
  public static function extractEntity(
    array $tokens
  ): string {
    [ $token ] = ParserUtils::sliceStatic( $tokens, 0, 1 );
    return $token->value;
  }

  public static function extractColumn(
    array $tokens
  ): string {
    [ $token ] = ParserUtils::sliceStatic( $tokens, 2, 1 );
    return $token->value;
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

    return $closure === MethodList::As->value ? MethodType::Alias : MethodType::Modify;
  } 

  public static function hasClosureByName(
    array $tokens,
    string $descriptor
  ): bool {
    $closureExists = ParserUtils::where( 
      $tokens, fn( ExpFieldMethod $expFieldMethod ) => (
        $expFieldMethod->descriptor === $descriptor
      )
    );

    return empty( $closureExists ) === false;
  }

  public static function extractSpecializedASTExpFieldClosure(
    array $tokens,
    array $descriptors
  ): array {
    $closures = ParserUtils::where( 
      $tokens, fn( ExpFieldMethod $expFieldMethod ) => (
        in_array( $expFieldMethod->descriptor, $descriptors )
      )
    );

    return $closures;
  }  

  public static function indexOf(
    array $tokens,
    string $descriptors    
  ): int {
    foreach( $tokens as $cursor => $token ){
      if( $token instanceof ExpFieldMethod ){
        if( $token->descriptor === $descriptors ){
          return $cursor;
        }
      }
    }

    return -1;    
  }

  public static function createSpecializedCaseASTExpFieldClosures( 
    Parser $parser,
    array $tokens,
    string $closure,
    array $closureChilds,
    array $whens = [],
    array $else = []
  ): array {
    $extractsClosure = ExpressionSelectUtils::extractSpecializedASTExpFieldClosure( $tokens, $closureChilds );
    $extractsIndexOf = ExpressionSelectUtils::indexOf( $tokens, $closure );

    $whenTokens = ParserUtils::where( $extractsClosure, fn( ExpFieldMethod $expFieldMethod ) => $expFieldMethod->descriptor === MethodList::When->value );
    $elseTokens = ParserUtils::where( $extractsClosure, fn( ExpFieldMethod $expFieldMethod ) => $expFieldMethod->descriptor === MethodList::Else->value );

    if( empty( $whenTokens ) === false ){
      $whens = ParserUtils::mapperStatic( $whenTokens, function( ExpFieldMethod $expFieldMethod ) use( $parser ) {
        $expFieldMethod->args = ParserUtils::mapperStatic( 
          ParserUtils::parserValue( $parser, $expFieldMethod->args ), 
            fn( ExpToken $expToken ) => $expToken->value
        );
        
        $args = array_filter( $expFieldMethod->args, fn( $arg ) => $arg !== ',' );
        $args = array_values( $args );
        
        [ $condition, $result ] = $args;
        return new ExpCaseWhen( $condition, $result );
      });
    }
    
    if( empty( $elseTokens ) === false ){
      [ $elseMethod ] = ParserUtils::mapperStatic( $elseTokens, function( ExpFieldMethod $expFieldMethod ) use( $parser ) {
        $expFieldMethod->args = ParserUtils::mapperStatic( ParserUtils::parserValue( $parser, $expFieldMethod->args ), fn( ExpToken $expToken ) => $expToken->value );
        return $expFieldMethod;
      });

      [ $resultElse ] = $elseMethod->args;
      $else = [ new ExpCaseElse( $resultElse ) ];
    }

    array_splice( $tokens, $extractsIndexOf, sizeof( $extractsClosure ), [ 
      new ExpFieldCaseMethod( descriptor: $closure, type: MethodType::Modify, whens: $whens, else: $else )
    ]);
     
    return $tokens;
  }

  public static function createSpecializedASTExpFieldClosures(
    array $tokens,
    Parser $parser,
    array $closuresListTypes = []
  ): array {
    $closuresListTypes = [
      MethodList::Case->value => [ MethodList::Case->value, MethodList::When->value, MethodList::Else->value ]
    ];

    foreach( $closuresListTypes as $closure => $closureChilds ){
      if( ExpressionSelectUtils::hasClosureByName( $tokens, $closure )){
        $tokens = match( $closure ){
          MethodList::Case->value => ExpressionSelectUtils::createSpecializedCaseASTExpFieldClosures( $parser, $tokens, $closure, $closureChilds, [], [] ),
            default => $tokens
        };
      }
    }

    return $tokens;
  }

  public static function extractASTExpFieldClosures(
    array $tokens,
    Parser $parser
  ): array {
    $closures = ParserUtils::mapperStatic(
      ParserUtils::groupByTypesStatic(
        [ T_OBJECT_OPERATOR ], ParserUtils::sliceStatic(
          $tokens, 4
        )
      ), fn( array $closure ) => (
        new ExpFieldMethod(
          $closure[0]->value, 
          ExpressionSelectUtils::fieldMethodType( $closure[0]->value ),
          ParserUtils::sliceStatic( $closure, 2, -1 )
        )
      )
    );

    return ExpressionSelectUtils::createSpecializedASTExpFieldClosures( $closures, $parser );
  }

  public static function createASTExpField(
    array $tokens,
    Parser $parser,
    Repository $repository
  ): ExpField {
    $closures = ExpressionSelectUtils::extractASTExpFieldClosures( $tokens, $parser );
    $entityStructure = $repository->getEntityStructureByString(
      $parser->scopes[ ExpressionSelectUtils::extractEntity( $tokens ) ]
    );

    $columnType = $entityStructure->getColumnType( ExpressionSelectUtils::extractColumn( $tokens ));
    $columnName = ExpressionSelectUtils::extractColumn( $tokens );
    return new ExpField( $entityStructure->getEntityAlias(), $columnName, $columnType, $closures );
  }   
}