<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->name('*.php');

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'single_quote' => true,
        'ordered_imports' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_summary' => false,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => false,
    ]);
