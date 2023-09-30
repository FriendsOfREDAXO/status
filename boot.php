<?php

$addon = rex_addon::get('status');

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($addon->getAssetsUrl('css/styles.css'));
}
