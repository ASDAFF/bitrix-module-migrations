<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Module;
use WS\Migrations\Tests\AbstractCase;

class UpdateTestCase extends AbstractCase {
    const FIXTURE_TYPE_ADD = 'add_collection';
    const FIXTURE_TYPE_UPDATE = 'update_collection';
    const FIXTURE_TYPE_IBLOCK_DELETE = 'delete_iblock';
    const FIXTURE_TYPE_SECTION_DELETE = 'delete_section';
    const FIXTURE_TYPE_PROPERTY_DELETE = 'delete_property';

    private $_processIblockId = null;

    public function name() {
        return '���������� ���������';
    }

    public function description() {
        return '������������ ���������� ��������� �������� ���������';
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
    }

    private function _applyFixtures($type) {
        $collector = Collector::createByFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$type.'.json');
        $this->assertNotEmpty($collector->getFixes());
        Module::getInstance()->applyFixesList($collector->getFixes());
    }

    public function testAdd() {
        /** @var $dbList \CDBResult */
        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();
        $beforeIds = array();
        while ($arIblock = $dbList->Fetch()) {
            $beforeIds[] = $arIblock['ID'];
        }
        $this->_applyFixtures(self::FIXTURE_TYPE_ADD);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();
        $afterIds = array();
        while ($arIblock = $dbList->Fetch()) {
            $afterIds[] = $arIblock['ID'];
        }

        $aAddedId = array_diff($afterIds, $beforeIds);
        $this->_processIblockId = array_shift($aAddedId);


        $this->assertNotEmpty($ibCountAfter, '������ �� ������ ��������������');
        $this->assertNotEquals($ibCountAfter, $ibCountBefore, '�� ���������� ������ ���������');
        $this->assertNotEmpty($this->_processIblockId, '���������� ������������� ������ ���������');

        $rsProps = \CIBlockProperty::GetList(null, array('IBLOCK_ID' => $this->_processIblockId));
        $this->assertNotEmpty($rsProps->AffectedRowsCount(), '���������� ����������� �������� ��������������� �����');

        $rsSections = \CIBlockSection::getList(null, array('IBLOCK_ID' => $this->_processIblockId), false, array('ID'));
        $this->assertNotEmpty($rsSections->AffectedRowsCount(), '���������� ����������� ������ ��������������� �����');
    }

    public function testUpdate() {
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test', '��������������� ������������������ �����');
        $this->_applyFixtures(self::FIXTURE_TYPE_UPDATE);
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test chenge NAME', '��� ��������� �� ����������');

        $sectionData = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($sectionData['NAME'], 'Test Section', '��� ��������� �� ����������');
    }

    public function testDelete() {
        $this->_applyFixtures(self::FIXTURE_TYPE_SECTION_DELETE);

        $rsSection = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEmpty($rsSection->getSelectedRowsCount(), '������ ���� ��������');

        $this->_applyFixtures(self::FIXTURE_TYPE_PROPERTY_DELETE);
        $rsProps = PropertyTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEquals($rsProps->getSelectedRowsCount(), 1, '� ��������� �������� ������ ���� ��������');

        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();

        $this->_applyFixtures(self::FIXTURE_TYPE_IBLOCK_DELETE);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();

        $this->assertNotEquals($ibCountBefore, $ibCountAfter, '�������� ����� ������');

        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();

        $this->assertEmpty($arIblock, '�������� ����������');
    }

    public function testCreateNewReferenceFixes() {
        $collector = Module::getInstance()->getDutyCollector();
        $fixes = $collector->getFixes();
        $this->assertNotEmpty($fixes, '���������� ������� �������� ���������� ������');
        foreach ($fixes as $fix) {
            if ($fix->getProcess() != 'reference') {
                $this->throwError('��� ���������� �������������� ������ ������');
            }
        }
    }
}