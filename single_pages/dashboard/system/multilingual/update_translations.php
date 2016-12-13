<?php
defined('C5_EXECUTE') or die('Access Denied.');

// Arguments
/* @var string $coreRelativePath */
/* @var string $packageRelativePath */
/* @var array $allLocales */
/* @var array $usedLocales */
/* @var MLocati\TranslationsUpdater\ResourceStats[] $allStats */
/* @var MLocati\TranslationsUpdater\ResourceStats|null $currentCoreStats */
/* @var MLocati\TranslationsUpdater\ResourceStats[] $otherCoresStats */
/* @var MLocati\TranslationsUpdater\ResourceStats[] $installedPackagesStats */
/* @var MLocati\TranslationsUpdater\ResourceStats[] $otherPackagesStats */

// Others
/* @var Concrete\Core\Page\View\PageView $this */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Validation\CSRF\Token $token */

?>

<form class="ccm-dashboard-content-form">
    <fieldset>
        <div class="form-group">
            <?php echo $form->label('component', t('Select Component')); ?>
            <select id="component" name="component" class="form-control">
                <option value="" selected="selected"><?php echo t('** Please Select'); ?></option>
                <?php
                if ($currentCoreStats !== null) {
                    ?>
                    <option value="<?php echo h($currentCoreStats->getHandle().'@'.$currentCoreStats->getVersion()); ?>"><?php echo h($currentCoreStats->getDisplayName(true)); ?></option>
                    <?php
                }
                if (!empty($installedPackagesStats)) {
                    ?>
                    <optgroup label="<?php echo t('Locally available packages'); ?>">
                        <?php
                        foreach ($installedPackagesStats as $stats) {
                            ?>
                            <option value="<?php echo h($stats->getHandle().'@'.$stats->getVersion()); ?>"><?php echo h($stats->getDisplayName(true)); ?></option>
                            <?php
                        }
                        ?>
                    </optgroup>
                    <?php

                }
                if (!empty($otherCoresStats)) {
                    ?>
                    <optgroup label="<?php echo t('Other core versions'); ?>">
                        <?php
                        foreach ($otherCoresStats as $stats) {
                            ?>
                            <option value="<?php echo h($stats->getHandle().'@'.$stats->getVersion()); ?>"><?php echo h($stats->getDisplayName(true)); ?></option>
                            <?php
                        }
                        ?>
                    </optgroup>
                    <?php
                }
                if (!empty($otherPackagesStats)) {
                    ?>
                    <optgroup label="<?php echo t('Other packages'); ?>">
                        <?php
                        foreach ($otherPackagesStats as $stats) {
                            ?>
                            <option value="<?php echo h($stats->getHandle().'@'.$stats->getVersion()); ?>"><?php echo h($stats->getDisplayName(true)); ?></option>
                            <?php
                        }
                        ?>
                    </optgroup>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <?php echo $form->label('locale', t('Select Language')); ?>
            <select id="locale" name="locale" class="form-control">
                <option value="" selected="selected"><?php echo t('** Please Select'); ?></option>
                <optgroup label="<?php echo t('Used languages'); ?>">
                    <?php
                    foreach ($usedLocales as $localeID => $localeName) {
                        ?>
                        <option value="<?php echo h($localeID); ?>"><?php echo h($localeName); ?></option>
                        <?php
                    }
                    ?>
                </optgroup>
                <optgroup label="<?php echo t('Other languages'); ?>">
                    <?php
                    foreach ($allLocales as $localeID => $localeName) {
                        if (!isset($usedLocales[$localeID])) {
                            ?>
                            <option value="<?php echo h($localeID); ?>"><?php echo h($localeName); ?></option>
                            <?php
                        }
                    }
                    ?>
                </optgroup>
            </select>
        </div>
    </fieldset>
    <div class="component-info" id="component-info-ok" style="display: none">
        <table class="table table-striped" style="table-layout: fixed">
            <colgroup>
                <col width="34%">
            </colgroup>
            <tbody>
                <tr>
                    <th><?php echo t('Translation progress'); ?></th>
                    <td id="component-info-progress"></td>
                </tr>
                <tr>
                    <th><?php echo t('Position of compiled (.mo) language file'); ?></th>
                    <td><code id="component-info-path"></code></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="component-info alert alert-warning" id="component-info-ko" style="display: none">
        <?php echo t('No translations available for the selected language'); ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="pull-right">
                <button class="btn btn-primary operation-download" data-format="mo" disabled="disabled"><?php echo t('Download .mo file'); ?></button>
                <button class="btn btn-primary operation-download" data-format="po" disabled="disabled"><?php echo t('Download .po file'); ?></button>
                <button class="btn btn-success" disabled="disabled" id="operation-update"><?php echo t('Update'); ?></button>
            </div>
        </div>
    </div>
</form>
<form style="display: none" method="POST" id="download-data" action="<?php echo h($this->action('downloadTranslations')) ?>">
    <?php $token->output('update-translations-download-translations'); ?>
    <input type="hidden" name="format" />
    <input type="hidden" name="locale" />
    <input type="hidden" name="stats" />
</form>
<script>
$(document).ready(function() {
var RELPATHS = {
    core: <?php echo json_encode($coreRelativePath); ?>,
    packages: <?php echo json_encode($packageRelativePath); ?>,
}
var allStats = <?php
$ja = [];
foreach ($allStats as $stats) {
    $ja[$stats->getHandle().'@'.$stats->getVersion()] = $stats->toArray();
}
echo json_encode($ja);
?>;
function getSelection()
{
    var key = $('#component').val(), result = {};
    result.stats = (key === '') ? null : allStats[key];
    result.locale = $('#locale').val() || '';
    return (result.stats && result.locale !== '') ? result : null;
}
function componentUpdated()
{
    var keys = getSelection();
    $('.ccm-dashboard-form-actions button').attr('disabled', 'disabled');
    if (keys !== null) {
        if (keys.stats.locales[keys.locale]) {
            var p = (keys.stats.handle === '') ? RELPATHS.core : RELPATHS.packages;
            p = p.replace(/<package>/g, keys.stats.handle.replace(/\-/g, '_'));            p = p.replace(/<locale>/g, keys.locale);
            $('#component-info-progress').text(keys.stats.locales[keys.locale] + '%');
            $('#component-info-path').text(p);
            $('#component-info-ko').hide();
            $('#component-info-ok').show();
            $('.ccm-dashboard-form-actions button').removeAttr('disabled', 'disabled');
        } else {
        	$('#component-info-ok').hide();
        	$('#component-info-ko').show();
        }
    } else {
        $('.component-info').hide();
    }
}
componentUpdated();
$('#component,#locale').on('change', function() {
    componentUpdated();
});
$('.operation-download').on('click', function(e) {
    e.preventDefault();
    var keys = getSelection();
    if (keys === null) {
        return;
    }
    $('#download-data')
        .find('[name="format"]').val($(this).data('format')).end()
        .find('[name="locale"]').val(keys.locale).end()
        .find('[name="stats"]').val(keys.stats.handle + '@' + keys.stats.version).end()
        .submit();
});
var processing = false;
$('#operation-update').on('click', function(e) {
    e.preventDefault();
    if (processing) {
        return;
    }
    var keys = getSelection();
    if (keys === null) {
        return;
    }
    processing = true;
    var $btn = $('#operation-update');
    $btn.width($btn.width()).html('<i class="fa fa-refresh fa-spin fa-fw"></i>')
    $.ajax({
    	data: {
            ccm_token: <?php echo json_encode($token->generate('update-translations-update-translations')); ?>,
            locale: keys.locale,
            stats: keys.stats.handle + '@' + keys.stats.version
    	},
    	dataType: 'json',
    	method: 'POST',
    	url: <?php echo json_encode($this->action('updateTranslations')); ?>
    })
    .always(function() {
        processing = false;
        $btn.css('width', 'auto').text(<?php echo json_encode(t('Update')); ?>);
    })
    .fail(function(xhr, status, error) {
        alert(error);
    })
    .success(function(data, status, xhr) {
        if (!data) {
        	alert(<?php echo json_encode(t('No server response')); ?>);
        } else if (data.error) {
            alert(data.errors.join('\n'));
    	} else {
            alert(data.message || <?php echo json_encode(t('No valid server response')); ?>);
    	}
    });
});

});
</script>
