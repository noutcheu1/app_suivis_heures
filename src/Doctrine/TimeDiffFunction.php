<?php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Fonction DQL personnalisée pour calculer la différence en heures
 * Usage: TIME_DIFF(heureFin, heureDebut)
 */
class TimeDiffFunction extends FunctionNode
{
    public $heureFin = null;
    public $heureDebut = null;

    public function parse(Parser $parser): void
    {
        $parser->match(Parser::T_IDENTIFIER); // TIME_DIFF
        $parser->match(Parser::T_OPEN_PARENTHESIS);
        $this->heureFin = $parser->ArithmeticPrimary();
        $parser->match(Parser::T_COMMA);
        $this->heureDebut = $parser->ArithmeticPrimary();
        $parser->match(Parser::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $heureFin = $this->heureFin->dispatch($sqlWalker);
        $heureDebut = $this->heureDebut->dispatch($sqlWalker);
        
        // Utiliser TIMESTAMPDIFF qui est standard SQL et supporté par MySQL/MariaDB
        return "TIMESTAMPDIFF(SECOND, {$heureDebut}, {$heureFin}) / 3600";
    }
}
