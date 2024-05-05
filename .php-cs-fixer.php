<?php

$finder = Symfony\Component\Finder\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('storage')
    ->exclude('bootstrap')
    ->exclude('node_modules')
    ->exclude('public')
    ->exclude('tests')
    ->exclude('database')
    ->exclude('resources');

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'no_unused_imports' => true,
    // Add more rules as per your preferences
])
    ->setFinder($finder);
