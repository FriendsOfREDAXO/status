<?php

use FriendsOfREDAXO\Status;

$accordionId = uniqid('collapse-', false);
$status = new Status();
?>

<div class="panel-group status-collapse" id="<?= $accordionId ?>" role="tablist" aria-multiselectable="true">
    <?php
    /**
     * Updates.
     */
    $availableUpdates = $status->getAvailableUpdates();
    $availableUpdatesCount = count($availableUpdates);
    $fragment = new rex_fragment();
    $fragment->setVar('title', "Neue Updates verfügbar [$availableUpdatesCount]");
    $fragment->setVar('contents', $availableUpdates);
    echo $fragment->parse('status/collapse-item.php');
    ?>

    <?php
    /**
     * Inactive AddOns.
     */
    $inactiveAddons = $status->getInactiveAddons();
    $inactiveAddonsCount = count($inactiveAddons);
    $fragment = new rex_fragment();
    $fragment->setVar('title', "Inaktive AddOns [$inactiveAddonsCount]");
    $fragment->setVar('contents', $inactiveAddons);
    echo $fragment->parse('status/collapse-item.php');
    ?>

    <?php
    /**
     * Security Headers.
     */
    $securityHeaders = $status->getSecurityHeaders();
    $availableSecurityHeaders = count(array_filter($securityHeaders, static function ($item) {
        return $item['status'];
    }));
    $securityHeadersCount = count($securityHeaders);

    $fragment = new rex_fragment();
    $fragment->setVar('title', "Security Headers [$availableSecurityHeaders/$securityHeadersCount]");
    $fragment->setVar('contents', $status->getSecurityHeaders());
    echo $fragment->parse('status/collapse-item.php');
    ?>

    <?php
    /**
     * Caching Headers.
     */
    $fragment = new rex_fragment();
    $fragment->setVar('title', 'Caching Headers');
    $fragment->setVar('contents', $status->getCachingHeaders());
    echo $fragment->parse('status/collapse-item.php');
    ?>

    <?php
    /**
     * Server Information.
     */
    $fragment = new rex_fragment();
    $fragment->setVar('title', 'Allgemeine Informationen');
    $fragment->setVar('contents', $status->getServerArchitecture());
    echo $fragment->parse('status/collapse-item.php');
    ?>

    <?php
    /**
     * Constants.
     */
    $fragment = new rex_fragment();
    $fragment->setVar('title', 'Konstanten');
    $fragment->setVar('contents', $status->getConstants());
    echo $fragment->parse('status/collapse-item.php');
    ?>
</div>
