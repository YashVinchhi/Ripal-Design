<?php

$finderClass = 'PhpCsFixer\\Finder';
$configClass = 'PhpCsFixer\\Config';

$finder = $finderClass::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'docs', 'tools', 'bootstrap-5.3.8-dist', 'uploads'])
    ->name('*.php');

return (new $configClass())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    ])
    ->setFinder($finder);
