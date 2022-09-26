<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__,
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$rules = [
    '@PSR12'       => true,
    'array_syntax' => [
        'syntax' => 'short'
    ],
    'ordered_imports'   => ['sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
    'no_useless_else'   => true, // 删除没有使用的else节点
    'no_useless_return' => true, // 删除没有使用的return语句
    // 'trailing_comma_in_multiline' => true,

    'no_empty_statement' => true, // 多余的分号

    'no_whitespace_in_blank_line' => true, // 删除空行中的空格
    'standardize_not_equals'      => true, // 使用 <> 代替 !=
    'combine_consecutive_unsets'  => true, // 当多个 unset 使用的时候，合并处理
    'concat_space'                => ['spacing' => 'one'], // .拼接必须有空格分割
    'array_indentation'           => true, // 数组的每个元素必须缩进一次
    'unary_operator_spaces'       => true,
    'blank_line_before_statement' => [
        'statements' => [
            'break',
            'continue',
            'declare',
            'return',
            'throw',
            'try'
        ],
    ],
    'binary_operator_spaces' => [
        'default'   => 'align_single_space',
        'operators' => [
            '=>' => 'align_single_space_minimal'
        ],
    ],
    'phpdoc_var_without_name' => true,
    'method_argument_space'   => [
        'on_multiline'                     => 'ensure_fully_multiline',
        'keep_multiple_spaces_after_comma' => true,
    ],
    'align_multiline_comment' => [
        'comment_type' => 'phpdocs_only',
    ],
    'lowercase_cast'                     => true, // 类型强制小写
    'lowercase_static_reference'         => true, // 静态调用为小写
    'general_phpdoc_tag_rename'          => true,
    'phpdoc_inline_tag_normalizer'       => true,
    'phpdoc_tag_type'                    => true,
    'phpdoc_no_empty_return'             => true,
    'phpdoc_trim'                        => true,
    'phpdoc_scalar'                      => true,
    'no_blank_lines_after_class_opening' => true,
    'phpdoc_separation'                  => false, // 不同注释部分按照单空行隔开
    'phpdoc_single_line_var_spacing'     => true,
    'phpdoc_indent'                      => true,
    'no_superfluous_phpdoc_tags'         => false, // 删除没有提供有效信息的@param和@return注解
    'phpdoc_align'                       => [
        'align' => 'vertical',
        'tags'  => [
            'param', 'throws', 'type', 'var', 'property'
        ]
    ],
];

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);
