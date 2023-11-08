<?php

$header = <<<'EOF'
    This file is part of HttpMessage package.

    (c) Pavel Vasin <phacman@yandex.ru>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP70Migration' => true,
        'header_comment' => ['header' => $header],
        'strict_comparison' => false,
        'strict_param' => true,
        'declare_strict_types' => false,
        'array_syntax' => ['syntax' => 'short'],
        'dir_constant' => true,
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'modernize_types_casting' => true,
        'self_accessor' => false,
        'multiline_whitespace_before_semicolons' => false,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_order' => true,
        'doctrine_annotation_braces' => true,
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => true,
        'psr_autoloading' => true,
        'no_php4_constructor' => true,
        'echo_tag_syntax' => ['format' => 'long'],
        'semicolon_after_instruction' => true,
        'align_multiline_comment' => true,
        'doctrine_annotation_array_assignment' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['package']],
        'list_syntax' => ['syntax' => 'short'],
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'no_superfluous_phpdoc_tags' => false,
        'visibility_required' => false, // new
        'single_line_comment_spacing' => false, // new
        'global_namespace_import' => false, // new
        'no_trailing_whitespace_in_string' => false, // new
        'phpdoc_separation' => false, // new
        'logical_operators' => false, // new
        'no_unneeded_control_parentheses' => false, // new
        'braces' => false, // new
        'blank_line_between_import_groups' => false, // new
        'php_unit_method_casing' => false, // new
        'fully_qualified_strict_types' => false, // new
        'native_constant_invocation' => ['fix_built_in' => false], // new
    ])
    ->setFinder($finder)
;

return $config;
