<?php
if ($_POST['apply']) {
    $collectors = \WS\Migrations\Module::getInstance()->getNotAppliedCollectors();
    foreach ($collectors as $collector) {
        foreach ($collector->getFixes() as $fix) {
            if (\WS\Migrations\Module::getInstance()->fixApply($fix)) {
                $appliedFixes[$fix->getName()]++;
            } else {
                $errorFixes[$fix->getName()]++;
            }
        }
    }
}

$fixes = array();
$collectors = \WS\Migrations\Module::getInstance()->getNotAppliedCollectors();
foreach ($collectors as $collector) {
    foreach ($collector->getFixes() as $fix) {
        $fixes[$fix->getName()]++;
    }
}

//--------------------------------------------------------------------------

/** @var $localization \WS\Migrations\Localization */
$localization;
/** @var $APPLICATION CMain */
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?><form method="POST" action="<?=
$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_main', array(
    array(
        "DIV" => "edit1",
        "TAB" => $localization->getDataByPath('title'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('title'),
    ) ,
));

$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));
$form->BeginNextFormTab();
$form->BeginCustomField('list', 'vv');
?>
<tr id="tr_ACTIVE_FROM"  style="color: #1e90ff;">
    <td width="30%"><?=$localization->getDataByPath('list')?>:</td>
    <td width="60%">
<?if($fixes):?>
        <ul>
<?foreach ($fixes as $fixName => $fixCount):?>
            <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
<?endforeach;?>
        </ul>
<?else:?>
<b>������ ����</b>
<?endif;?>
    </td>
</tr>
<?
$form->EndCustomField('data');
//--------------------
$form->AddSection('lastSetup', '�������� ��������� 19-00 �������� ������ [33]');
$form->BeginCustomField('appliedList', 'vv');
?>
<tr id="tr_ACTIVE_FROM" style="color: #32cd32;">
    <td width="30%"><?=$localization->getDataByPath('appliedList')?>:</td>
    <td width="60%">
<?if($appliedFixes):?>
        <ul>
<?foreach ($appliedFixes as $fixName => $fixCount):?>
            <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
<?endforeach;?>
        </ul>
<?else:?>
<b>������ ����</b>
<?endif;?>
    </td>
</tr>
<?
$form->EndCustomField('appliedList');
//--------------------
$form->BeginCustomField('errorList', 'vv');
?>
<tr id="tr_ACTIVE_FROM"  style="color: #ff0000;">
    <td width="30%"><?=$localization->getDataByPath('errorList')?>:</td>
    <td width="60%">
<?if($errorFixes):?>
        <ul>
<?foreach ($errorFixes as $fixName => $fixCount):?>
            <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
<?endforeach;?>
        </ul>
<?else:?>
<b>������ ����</b>
<?endif;?>
    </td>
</tr>
<?
$form->EndCustomField('errorList');
$form->EndTab();
!$fixes && $form->bPublicMode = true;
$form->Buttons(array('btnSave' => false, 'btn�pply' => true));
$form->Show();
?></form><?
