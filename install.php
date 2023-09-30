<?php

$addon = rex_addon::get('status');

if (class_exists('rex_scss_compiler')) {
    $compiler = new rex_scss_compiler();
    $compiler->setRootDir(rex_path::addon('status/scss'));
    $compiler->setScssFile([$addon->getPath('scss/styles.scss')]);
    $compiler->setCssFile($addon->getPath('assets/css/styles.css'));
    $compiler->compile();
}
