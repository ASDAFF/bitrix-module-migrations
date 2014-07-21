<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\Processes\AddProcess;
use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\SubjectHandlers\IblockPropertyHandler;
use WS\Migrations\SubjectHandlers\IblockSectionHandler;
use WS\Migrations\Tests\AbstractCase;

class FixTestCase extends AbstractCase {

    /**
     * @var Collector
     */
    private $_currentDutyCollector;

    const VERSION = 'testVersion';

    public function name() {
        return '������������ �������� ���������';
    }

    public function description() {
        return '�������� �������� ��������� ��� ��������� ��������� ���������� �������';
    }

    public function setUp() {
        \CModule::IncludeModule('iblock');
    }

    private function _getCollectorFixes($process, $subject = null) {
        if (!$this->_currentDutyCollector) {
            throw new \Exception('Duty collector not exists');
        }
        $fixes = $this->_currentDutyCollector->getFixesData(self::VERSION);
        $res = array();
        foreach ($fixes as $fixData) {
            $fixData['process'] == $process && $fixData['subject'] == $subject && $res[] = $fixData;
        }
        return $res;
    }

    private function _injectDutyCollector() {
        $collector = Collector::createInstance(__DIR__);
        Module::getInstance()->injectDutyCollector($collector);
        $this->_currentDutyCollector = $collector;
        return $collector;
    }

    public function testIblockAdd() {
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
        $this->assertEquals(3, count($refFixes), '������� ������ ���� 3');
    }
}