<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'no_unused_imports' => true,
        'phpdoc_no_empty_return' => false,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'multiline_comment_opening_closing' => true,
        'class_attributes_separation' => [
            'elements' => ['method' => 'one']
        ],
        'single_line_throw' => false,
        'native_function_invocation' => [
            'include' => ['@internal'],
            'scope' => 'all',
        ],
    ])
    ->setFinder($finder)
    ->setIndent("    ") // 4 spaces
    ->setLineEnding("\n");
