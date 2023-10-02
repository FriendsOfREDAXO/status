<?php

$addon = rex_addon::get('status');

/**
 * Add the assets to the backend.
 */
if (rex::isBackend() && rex::getUser()) {
    try {
        rex_view::addCssFile($addon->getAssetsUrl('css/styles.css'));
        rex_view::addJsFile($addon->getAssetsUrl('js/scripts.js'));
    } catch (rex_exception $e) {
        rex_logger::logException($e);
    }

    rex_view::setJsProperty('status_ajax_url', rex_url::backendController(['rex-api-call' => 'status_dir_size']));
}

/**
 * Manipulate the page navigation.
 * Add a badge to the install page, if there are updates available.
 * @param rex_extension_point $ep
 */
\rex_extension::register('PAGE_NAVIGATION',
    static function (rex_extension_point $ep) {
        /** @var rex_be_navigation $navigation */
        $navigation = $ep->getSubject();

        /**
         * Get the available updates.
         * Should avoid to call internal methods ¯\_(ツ)_/¯.
         */
        $availableUpdates = rex_install_packages::getUpdatePackages();

        if (empty($availableUpdates)) {
            return;
        }

        /**
         * Get the pages property from the rex_be_navigation object.
         * Use reflection to get access to the private property.
         */
        $reflection = new ReflectionObject($navigation);
        $property = $reflection->getProperty('pages');
        $property->setAccessible(true);

        /**
         * Loop through the pages and check if the page is the packages page.
         */
        foreach ($property->getValue($navigation)['system'] as $page) {
            /**
             * Check if the page is the packages page.
             * @var rex_be_page_main $page
             */
            if ('install' !== $page->getKey()) {
                continue;
            }

            /**
             *Manipulate the page object.
             */
            $availableUpdatesCount = count($availableUpdates);
            $title = $page->getTitle();
            $page->setTitle("$title <span class='badge'>$availableUpdatesCount</span>");
        }
    },
);
