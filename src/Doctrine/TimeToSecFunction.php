<?php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Fonction DQL personnalisée pour TIME_TO_SEC
 * Usage: TIME_TO_SEC(heure)
 */
class TimeToSecFunction extends FunctionNode
{
    public $heure = null;

    public function parse(Parser $parser): void
    {
        $parser->match(Parser::T_IDENTIFIER); // TIME_TO_SEC
        $parser->match(Parser::T_OPEN_PARENTHESIS);
        $this->heure = $parser->ArithmeticPrimary();
        $parser->match(Parser::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $heure = $this->heure->dispatch($sqlWalker);
        
        // Utiliser TIME_TO_SEC qui est standard MySQL
        return "TIME_TO_SEC({$heure})";
    }
}
