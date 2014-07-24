<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\Processes\AddProcess;
use WS\Migrations\Processes\DeleteProcess;
use WS\Migrations\Processes\UpdateProcess;
use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\SubjectHandlers\IblockPropertyHandler;
use WS\Migrations\SubjectHandlers\IblockSectionHandler;
use WS\Migrations\Tests\AbstractCase;

class FixTestCase extends AbstractCase {

    /**
     * @var Collector
     */
    private $_currentDutyCollector;

    const VERSION = 'test';

    private $_iblockId, $_propertyId, $_sectionId;

    public function name() {
        return '������������ �������� ���������';
    }

    public function description() {
        return '�������� �������� ��������� ��� ��������� ��������� ���������� �������';
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
    }

    /**
     * @param $process
     * @param null $subject
     * @return array ������ �������� ������
     * @throws \Exception
     */
    private function _getCollectorFixes($process, $subject = null) {
        if (!$this->_currentDutyCollector) {
            throw new \Exception('Duty collector not exists');
        }
        $fixes = $this->_currentDutyCollector->getFixesData(self::VERSION);
        $res = array();
        foreach ($fixes as $fixData) {
            $fixData['process'] == $process
                &&
            ($subject && $fixData['subject'] == $subject || !$subject)
                &&
            $res[] = $fixData;
        }
        return $res;
    }

    private function _injectDutyCollector() {
        $collector = Collector::createInstance(__DIR__);
        $collector->notStored();
        Module::getInstance()->injectDutyCollector($collector);
        $this->_currentDutyCollector = $collector;
        return $collector;
    }

    public function testAdd() {
        $this->_injectDutyCollector();
        $ibType = \CIBlockType::GetList()->Fetch();
        $ib = new \CIBlock;

        $ibId = $ib->Add(array(
            'IBLOCK_TYPE_ID' => $ibType['ID'],
            'NAME' => 'New Iblock',
            'SITE_ID' => 's1'
        ));

        $this->assertNotEmpty($ibId, '�� ������ ������������� ���������.'.$ib->LAST_ERROR);

        $prop = new \CIBlockProperty();
        $propId = $prop->Add(array(
            'IBLOCK_ID' => $ibId,
            'CODE' => 'propCode',
            'NAME' => 'Property NAME'
        ));
        $this->assertNotEmpty($propId, '�� ������� �������� ���������.'.$prop->LAST_ERROR);

        $sec = new \CIBlockSection();
        $secId = $sec->Add(array(
            'IBLOCK_ID' => $ibId,
            'NAME' => 'Iblock Section'
        ));
        $this->assertNotEmpty($secId, '�� ������� ������ ���������.'.$sec->LAST_ERROR);

        // � ����� ������ ���������

        // ������ �� ���������� ��
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockHandler::className()));
        // ������ �� ���������� ��������
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockPropertyHandler::className()));
        // ������ �� ���������� ������
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockSectionHandler::className()));

        $refFixes = $this->_getCollectorFixes('reference');
        // �������� ���������
        Module::getInstance()->commitDutyChanges();
        // ��������� ������ ������� ���������� (� ����)
        /** @var $logRecords AppliedChangesLogModel[] */
        $logRecords = AppliedChangesLogModel::find(array(
            'order' => array(
                'id' => 'desc'
            ),
            'limit' => 3
        ));

        $this->assertEquals(3, count($logRecords));
        foreach ($logRecords as $logRecord) {
            if ($logRecord->processName != AddProcess::className()) {
                $this->throwError('���������� �������� ���� ������ ���� ������� ����������');
            }
            $data = $logRecord->updateData;
            switch ($logRecord->subjectName) {
                case IblockHandler::className():
                    (!$data['iblock'] || ($data['iblock']['ID'] != $ibId)) && $this->throwError('�������� ����������������� � ����������, ��� '.$data['iblock']['ID'].', ����� '.$ibId);
                    break;
                case IblockPropertyHandler::className():
                    ($data['ID'] != $propId) && $this->throwError('�������� ������������������ � ����������, �������� - '.$propId.' �������� '.$data['ID']);
                    break;
                case IblockSectionHandler::className():
                    $data['ID'] != $secId && $this->throwError('������ ������������������ � ����������, �������� - '.$secId.' �������� '.$data['ID']);
                    break;
            }
        }

        // ��������� ��� ���� ������ � ���������
        $this->assertEquals(3, count($refFixes), '������ ������ ���� 3');

        $this->_iblockId = $ibId;
        $this->_propertyId = $propId;
        $this->_sectionId = $secId;
    }

    /**
     * ����������� �� ����������
     * @after testAdd
     */
    public function testUpdate() {
        $this->_injectDutyCollector();
        $this->assertNotEmpty($this->_iblockId);
        $this->assertNotEmpty($this->_propertyId);
        $this->assertNotEmpty($this->_sectionId);

        $arIblock = \CIBlock::GetArrayByID($this->_iblockId);
        $arIblock['NAME'] .= '2';
        $name = $arIblock['NAME'];

        $iblock = new \CIBlock();
        $updateResult = $iblock->Update($this->_iblockId, $arIblock);

        $this->assertTrue($updateResult, '��������� ���������� �������������');
        // ��� ������ ������������ ������ ��� ������
        $fixes = $this->_getCollectorFixes(UpdateProcess::className());
        $this->assertEquals(count($fixes), 1, '������� ����� �������� ����������');
        $this->assertEquals($fixes[0]['data']['iblock']['NAME'], $name, '�������� �� ��������� �����');

        // �������� ���������
        Module::getInstance()->commitDutyChanges();
    }

    /**
     * ����������� �� ����������
     * @after testUpdate
     */
    public function testDelete() {
        $this->_injectDutyCollector();
        $deleteResult = \CIBlock::Delete($this->_iblockId);

        $this->assertTrue($deleteResult, '�������� ������ ���� ������ �� ��');

        $this->assertCount($this->_getCollectorFixes(DeleteProcess::className()), 3, '������ ���� ������ ��������: ������, ��������, ��������');
        $this->assertCount($sectionFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockSectionHandler::className()), 1, '������ ���� ������ ��������: ������');
        $this->assertCount($propsFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockPropertyHandler::className()), 1, '������ ���� ������ ��������: �������� ���������');
        $this->assertCount($iblockFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockHandler::className()), 1, '������ ���� ������ ��������: ��������');

        $sectionFixData = array_shift($sectionFixesList);
        $this->assertTrue(is_scalar($sectionFixData['data']), '������� ���������� ��� �������� ������ ������ ���� �������������, � ��� - '.self::exportValue($sectionFixData['data']));

        $propFixData = array_shift($propsFixesList);
        $this->assertTrue(is_scalar($propFixData['data']), '������� ���������� ��� �������� �������� ��������� ������ ���� �������������, � ��� - '.self::exportValue($propFixData['data']));

        $iblockFixData = array_shift($iblockFixesList);
        $this->assertTrue(is_scalar($iblockFixData['data']), '������� ���������� ��� �������� ��������� ������ ���� �������������, � ��� - '.self::exportValue($iblockFixData['data']));
        $this->assertNotEmpty($iblockFixData['originalData'], '������ �������� ������ ���������� ���������');

        // �������� ���������
        Module::getInstance()->commitDutyChanges();
    }

    /**
     * @after testDelete
     */
    public function testRollbackDelete() {
        /** @var $list AppliedChangesLogModel[] */
        $list = AppliedChangesLogModel::find(array(
            'limit' => 3,
            'order' => array('id' => 'DESC')
        ));
        $this->assertCount($list, 3, '������ ���� ��������� ��� ������');

        foreach ($list as $lItem) {
            $this->assertTrue($lItem->processName == DeleteProcess::className(), '������������� ������� ������ ���� - ���������');
        }
        $rsIblock = \CIBlock::getList();
        $countIbBefore = $rsIblock->SelectedRowsCount();
        $iblocksBefore = array();
        while ($arIb = $rsIblock->Fetch()) {
            $iblocksBefore[] = $arIb['ID'];
        }
        Module::getInstance()->rollbackByLogs($list ?: array());
        $rsIblock = \CIBlock::getList();
        $countIbAfter = $rsIblock->SelectedRowsCount();
        $iblocksAfter = array();
        while ($arIb = $rsIblock->Fetch()) {
            $iblocksAfter[] = $arIb['ID'];
        }
        $rebuildIblockId = array_diff($iblocksAfter, $iblocksBefore);
        $rebuildIblockId = array_shift($rebuildIblockId);

        $this->assertEquals($countIbAfter, $countIbBefore + 1, '������ ��������� ������ ���� �������������');
        $this->assertEquals($rebuildIblockId, $this->_iblockId, '�������� ������������, ������������� �������');

        $rsProp = PropertyTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $rebuildIblockId
            )
        ));
        $this->assertTrue($rsProp->getSelectedRowsCount() > 0, '������ �������������� �������� ���������������� ��������� - '.$rebuildIblockId);

        $rsSections = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $rebuildIblockId
            )
        ));
        $this->assertTrue($rsSections->getSelectedRowsCount() > 0, '������ �������������� ������(�������) ���������������� ��������� - '.$rebuildIblockId);
    }
}