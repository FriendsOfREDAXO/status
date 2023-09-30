<?php
$collapseId = uniqid('collapse-', false);
$headingId = uniqid('heading-', false);
?>

<?php if (!empty($this->contents) && is_array($this->contents) && !empty($this->title)) : ?>
    <div class="panel panel-primary">
        <div class="panel-heading p-0" role="tab" id="<?= $headingId ?>">
            <h4 class="panel-title">
                <a class="collapsed block p-panel" role="button" data-toggle="collapse" href="#<?= $collapseId ?>"
                   aria-expanded="true"
                   aria-controls="<?= $collapseId ?>">
                    <strong>
                        <?= $this->title ?>
                    </strong>
                </a>
            </h4>
        </div>
        <div id="<?= $collapseId ?>" class="panel-collapse collapse" role="tabpanel"
             aria-labelledby="<?= $headingId ?>">
            <div class="panel-body p-0">
                <table class="table table-hover">
                    <?php foreach ($this->contents as $content) : ?>
                        <?php
                            $itemClass = '';

                            if (isset($content['status'])) {
                                $itemClass = $content['status'] ? 'success' : 'warning';
                            }
                        ?>
                        <tr class="<?= $itemClass ?>">
                            <td>
                                <?php if (isset($content['status'])) : ?>
                                    <?php if ($content['status']) : ?>
                                        <i class="rex-icon fa-check text-success"></i>
                                    <?php else: ?>
                                        <i class="rex-icon fa-exclamation-triangle text-warning"></i>
                                    <?php endif ?>
                                <?php else: ?>
                                    <i class="rex-icon rex-icon-info text-info"></i>
                                <?php endif ?>
                                &nbsp;
                                <strong>
                                    <?= $content['title'] ?>
                                </strong>
                            </td>
                            <td>
                                <?= $content['value'] ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>
