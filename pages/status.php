<?php

/** @var rex_addon $this */

$fragment = new rex_fragment();
$fragment->setVar('flush', true);
$content = $fragment->parse('status/collapse.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', 'Status');
$fragment->setVar('body', $content, false);
$content = $fragment->parse('core/page/section.php');

echo $content;
