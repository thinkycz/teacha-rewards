<?php

declare(strict_types=1);

namespace Thinkycz\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FunctionLike>
 */
class FunctionRequired implements Rule
{
    /**
     * Get node type.
     */
    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    /**
     * Process node.
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
            return [];
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            if (\str_starts_with($node->name->name, 'test_')) {
                return [];
            }
        }

        if ($node->getDocComment() !== null) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Function has no doc comment.')
                ->identifier('thinkycz.functionDocCommentRequired')
                ->build(),
        ];
    }
}
