<?php

declare(strict_types=1);

namespace Thinkycz\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Property>
 */
class PropertyRequired implements Rule
{
    /**
     * Get node type.
     */
    public function getNodeType(): string
    {
        return Property::class;
    }

    /**
     * Process node.
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->getDocComment() !== null) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Property has no doc comment.')
                ->identifier('thinkycz.propertyDocCommentRequired')
                ->build(),
        ];
    }
}
