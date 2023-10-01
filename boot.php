<?php

$addon = rex_addon::get('status');

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($addon->getAssetsUrl('css/styles.css'));
    rex_view::addJsFile($addon->getAssetsUrl('js/scripts.js'));
    rex_view::setJsProperty('status_ajax_url', rex_url::backendController(['rex-api-call' => 'status_dir_size']));
}
