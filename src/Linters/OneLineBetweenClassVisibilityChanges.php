<?php

namespace Tighten\TLint\Linters;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\Parser;
use Tighten\TLint\BaseLinter;

class OneLineBetweenClassVisibilityChanges extends BaseLinter
{
    public const description = 'Class members of differing visibility must be separated by a blank line';

    public function lint(Parser $parser)
    {
        $traverser = new NodeTraverser;

        $notSeparatedByBlankLine = [];

        $visitor = new FindingVisitor(function (Node $node) use (&$notSeparatedByBlankLine) {
            if ($node instanceof Class_) {
                $previousNode = null;

                $constAndPropertyNodes = array_filter($node->stmts, function ($stmt) {
                    return in_array(get_class($stmt), [Node\Stmt\ClassConst::class, Node\Stmt\Property::class]);
                });

                foreach ($constAndPropertyNodes as $node) {
                    // Ignore the very first const/property
                    if (is_null($previousNode)) {
                        $previousNode = $node;
                        continue;
                    }

                    // Ignore nodes with exactly the same visibility
                    if ($previousNode->flags === $node->flags) {
                        $previousNode = $node;
                        continue;
                    }

                    // Ignore nodes separated by exactly one blank line and no comments
                    if ($node->getStartLine() - $previousNode->getEndLine() === 2 && empty($node->getComments())) {
                        $previousNode = $node;
                        continue;
                    }

                    if (! empty($comments = $node->getComments())) {
                        // Get all lines between these nodes that are part of any comment
                        $commentLines = array_merge(...array_map(function ($comment) {
                            return range($comment->getStartLine(), $comment->getEndLine());
                        }, $comments));

                        // Get all lines between these nodes
                        $allLinesBetweenNodes = range($previousNode->getEndLine() + 1, $node->getStartLine() - 1);

                        // Ignore nodes separated by comments when there is exactly one blank line within the comments
                        if (count(array_diff($allLinesBetweenNodes, $commentLines)) === 1) {
                            $previousNode = $node;
                            continue;
                        }
                    }

                    $notSeparatedByBlankLine[] = $node;

                    $previousNode = $node;
                }
            }

            return false;
        });

        $traverser->addVisitor($visitor);

        $traverser->traverse($parser->parse($this->code));

        return $notSeparatedByBlankLine;
    }
}
