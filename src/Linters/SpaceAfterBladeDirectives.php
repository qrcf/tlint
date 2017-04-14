<?php

namespace Tighten\Linters;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\Parser;
use Tighten\AbstractLinter;

class SpaceAfterBladeDirectives extends AbstractLinter
{
    private const SPACE_AFTER = [
        'if',
        'elseif',
        'for',
        'foreach',
        'unless',
        'forelse',
    ];

    public function lintDescription()
    {
        return 'Put a space between blade control structure names and the opening paren: `@if(` -> `@if (`';
    }

    /**
     * @param Parser $parser
     * @return Node[]
     */
    public function lint(Parser $parser)
    {
        $foundNodes = [];

        foreach ($this->getCodeLines() as $line => $codeLine) {
            $matches = [];

            // https://github.com/illuminate/view/blob/master/Compilers/BladeCompiler.php#L271
            preg_match(
                '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
                $codeLine,
                $matches
            );

            if (in_array($matches[1] ?? null, self::SPACE_AFTER) && ($matches[2] ?? null) === '') {
                $foundNodes[] = new class(['startLine' => $line + 1]) extends NodeAbstract {
                    public function getSubNodeNames()
                    {
                        return [];
                    }
                };
            }
        }

        return $foundNodes;
    }
}