<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use Bitrix\Iblock\SectionTable;
use Bitrix\Main\DB\Exception;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;

class IblockSectionHandler extends BaseSubjectHandler {

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockSection.name');
    }

    public function getIdByChangeMethod($method, $data = array()) {
        switch ($method) {
            case Module::FIX_CHANGES_ADD_KEY:
            case Module::FIX_CHANGES_BEFORE_CHANGE_KEY:
            case Module::FIX_CHANGES_AFTER_CHANGE_KEY:
            case Module::FIX_CHANGES_AFTER_DELETE_KEY:
                return $data[0]['ID'];
            case Module::FIX_CHANGES_BEFORE_DELETE_KEY:
                return $data[0];
        }
        return null;
    }

    public function getSnapshot($id, $dbVersion = null) {
        if (!$id) {
            return false;
        }
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);
        $data = SectionTable::GetByID($id)->Fetch();
        $data['~reference'] = $this->getReferenceValue($id);
        return $data;
    }

    /**
     * @param $data
     * @param null $dbVersion
     * @throws \Exception
     * @return ApplyResult
     */
    public function applySnapshot($data, $dbVersion = null) {
        $data = $this->handleNullValues($data);
        $sec = new \CIBlockSection();
        $res = new ApplyResult();

        $extId = $data['ID'];
        if ($dbVersion) {
            $data['IBLOCK_ID'] = $this->getReferenceController()->getCurrentIdByOtherVersion($data['IBLOCK_ID'], ReferenceController::GROUP_IBLOCK, $dbVersion);
            $data['IBLOCK_SECTION_ID'] && $data['IBLOCK_SECTION_ID'] = $this->getCurrentVersionId($data['IBLOCK_SECTION_ID'], $dbVersion);
            $id = $this->getCurrentVersionId($extId, $dbVersion);
        } else {
            $id = $extId;
        }
        if (!$dbVersion && !SectionTable::getList(array('filter' => array('=ID' => $id)))->fetch()) {
            $addRes = SectionTable::add(array(
                'ID' => $id,
                'IBLOCK_ID' => $data['IBLOCK_ID'],
                'TIMESTAMP_X' => new DateTime(),
                'NAME' => $data['NAME'],
                'DESCRIPTION_TYPE' => $data['DESCRIPTION_TYPE'],
            ));
            if (!$addRes->isSuccess()) {
                throw new \Exception('�� ������� ����������� ������(������) ������� ������. ' . implode(', ', $addRes->getErrorMessages())."\n".var_export($data, true));
            }
        }
        unset($data['CREATED_BY'], $data['MODIFIED_BY']);
        if ($id && ($currentData = SectionTable::getById($id)->fetch())) {
            $data['PICTURE'] = $currentData['PICTURE'];
            $data['DETAIL_PICTURE'] = $currentData['DETAIL_PICTURE'];
            $res->setSuccess((bool)$sec->Update($id, $data));
        } else {
            unset($data['PICTURE'], $data['DETAIL_PICTURE']);
            $res->setSuccess((bool) ($id = $sec->Add($data)));
            $this->registerCurrentVersionId($id, $this->getReferenceValue($extId, $dbVersion));
        }
        $res->setId($id);
        $res->setMessage($sec->LAST_ERROR);
        return $res;
    }

    /**
     * Delete subject record
     * @param $id
     * @param null $dbVersion
     * @return ApplyResult
     */
    public function delete($id, $dbVersion = null) {
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);


        $sec = new \CIBlockSection();
        $res = new ApplyResult();
        $res
            ->setSuccess((bool) $sec->Delete($id))
            ->setMessage($sec->LAST_ERROR);
        $res->isSuccess() && $this->removeCurrentVersion($id);
        return $res;
    }

    protected function getSubjectGroup() {
        return ReferenceController::GROUP_IBLOCK_SECTION;
    }

    public function existsIds() {
        $dbRes = SectionTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($item = $dbRes->fetch()) {
            $res[] = $item['ID'];
        }
        return $res;
    }

    public function required() {
        return false;
    }

    protected function getExistsSubjectIds() {
        $rs = SectionTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($arSection = $rs->fetch()) {
            $res[] = $arSection['ID'];
        }
        return $res;
    }
}