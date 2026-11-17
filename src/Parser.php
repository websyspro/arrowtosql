<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Expressions\ExpToken;
use ReflectionFunction;
use PhpToken;
use Closure;

use function is_array;
use function sprintf;

class Parser
extends ParserUtils
{
  public array $uses = [];
  public array $lines = [];
  public array $scopes = [];
  public array $tokens = [];
  public array $expression = [];
  public array $staticVariables = [];
  public ExpressionType $expressionType;
  public ReflectionFunction $reflectionFunction;

  public function __construct(
    private Closure|array $closureOrArray,
    public array $parentUses = [],
    public array $parentScopes = [],
    public Closure|null $parentClosure = null
  ){
    $this->setExpressionType();
    $this->setReflectionFunction();
    $this->setExtractLinesFromFile();
    $this->setExtractUsesFromFile();
    $this->setExtractStaticsFromFile();
    $this->setExtractExpressions();
    $this->setExtractScopes();
    $this->setClearVariables();
  }

  public function getClosure(
  ): Closure {
    return $this->closureOrArray;
  }

  private function setExpressionType(
  ): void {
    $this->expressionType = new ExpressionType();
  }

  private function setReflectionFunction(
  ): void {
    if( $this->closureOrArray instanceof Closure ){
      $this->reflectionFunction = new ReflectionFunction( $this->closureOrArray );
    }
  }

  private function setExtractLinesFromFile(
  ): void {
    if( isset($this->reflectionFunction) && $this->reflectionFunction instanceof ReflectionFunction ){
      $this->lines = file(
        $this->reflectionFunction
          ->getFileName(), FILE_IGNORE_NEW_LINES
      );
    }    
  }

  private function setExtractUsesFromFile(
  ): void {
    if( empty( $this->parentUses ) === false ){
      $this->uses = [
        ...$this->parentUses,
        ...$this->uses
      ];      
    }

    if( empty( $this->lines ) === false ){
      foreach($this->lines as $line){
        $line = trim( $line );

        if(preg_match("#^(class|function|abstract|final)\s#", $line)){
          break;
        }

        if(preg_match("#^use\s+(.+);$#", $line, $matches)){
          $fqcn = trim($matches[1]);

          if(preg_match('/^(.+)\s+as\s+(\w+)$/i', $fqcn, $aliasParts)) {
            $alias = trim( $aliasParts[ 2 ]);
            $fqcn = trim( $aliasParts[ 1 ]);
          } else {
            $parts = explode('\\', $fqcn);
            $alias = end( $parts );
          }

          $this->uses[ $alias ] = $fqcn;
        }
      }        
    }    
  }

  private function setExtractStaticsFromFile(
  ): void {
    if( isset($this->reflectionFunction) && $this->reflectionFunction instanceof ReflectionFunction ){
      $this->staticVariables = $this->reflectionFunction->getStaticVariables();
    } else if( isset($this->parentClosure) && $this->parentClosure instanceof ReflectionFunction ){
      $this->staticVariables = $this->parentClosure->getStaticVariables();
    }   
  }

  private function setExtractExpressions(
  ): void {
    if( isset($this->reflectionFunction) && $this->reflectionFunction instanceof ReflectionFunction ){
      $this->expression = $this->mapper(
        $this->slice(
          $this->lines, $this->reflectionFunction->getStartLine() - 1, 
          $this->reflectionFunction->getEndLine() - $this->reflectionFunction->getStartLine() + 1
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
          $phpToken->id, trim( $phpToken->text, "\"" ), TokenName::get( $phpToken->id )
        )
      );    

      $this->expression = $this->filter(
        $this->expression, fn( ExpToken $token ) => $token->key !== T_WHITESPACE
      );

      $this->expression = $this->slice(
        $this->expression, $this->indexOf( $this->expression, T_FN ) + 1
      );       
    } else if( is_array( $this->closureOrArray )){
      $this->expression = $this->closureOrArray;
    }
  }

  private function setExtractScopes(
  ): void {
    $this->mapper(
      $this->groupByTypesComma(
        $this->slice( $this->expression, $this->indexOf( $this->expression, T_START_PARENTESES ) + 1,
          $this->indexOf( $this->expression, T_END_PARENTESES ) - 1
        )
      ), function( array $groupTokens ){
        [ $tokenEntity, $tokenVar ] = $groupTokens;
        $this->scopes[ $tokenVar->value ] = $this->uses[ $tokenEntity->value ];
      }
    );

    $this->scopes = [
      ...$this->scopes,
      ...$this->parentScopes
    ];

    $this->tokens = $this->contextsNotEnds(
      $this->slice( $this->expression, $this->indexOf( $this->expression, T_DOUBLE_ARROW ) + 1)
    );    
  }

  private function setClearVariables(
  ): void {
    unset(
      $this->lines,
      $this->expression,
      $this->parentUses,
      $this->parentScopes,
    );
  }
}