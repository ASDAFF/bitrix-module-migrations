<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Module;
use WS\Migrations\Tests\AbstractCase;

class UpdateTestCase extends AbstractCase {
    const FIXTURE_TYPE_ADD = 'add_collection';
    const FIXTURE_TYPE_UPDATE = 'update_collection';
    const FIXTURE_TYPE_DELETE = 'delete_collection';

    public function name() {
        return '���������� ���������';
    }

    public function description() {
        return '������������ ���������� ��������� �������� ���������';
    }

    public function init() {
        \CModule::IncludeModule('iblock');
    }

    private function _applyFixtures($type) {
        $collector = Collector::createByFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$type.'.json');
        $this->assertNotEmpty($collector->getFixes());
        Module::getInstance()->applyFixesList($collector->getFixes());
    }

    public function testAdd() {

        // ��������/�������� ��������� ������� ��
        /**
         *  - ����������� ����������
         */
        /** @var $dbList \CDBResult */
        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();
        $this->_applyFixtures(self::FIXTURE_TYPE_ADD);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();

        $this->assertNotEmpty($ibCountAfter, '������ �� ������ ��������������');
        $this->assertNotEquals($ibCountAfter, $ibCountBefore, '�� ���������� ������ ���������');

        // �������� ��������� ������� �����
        /**
         *  - ���������� ���������
         *  - ������� ������� ���������
         *  - ������� ������ ���������
         *  - ������������ ������ �������� � ����������
         *  - ����� ����������� ������ ������ ��������� �� ���������� ����� �����
         */
        $this->assertTrue(true);
    }

    public function testUpdate() {
        return;

        // ��������/�������� ��������� ������� ��
        /**
         *  - �������� ������ (����� name)
         */
        $this->_applyFixtures(self::FIXTURE_TYPE_UPDATE);

        // �������� ��������� ������� �����
        /**
         *  - ��������� ������ ����
         *  - ���������
         *  - ������ ���������
         */
    }

    public function testDelete() {
        return;

        // ��������/�������� ��������� ������� ��
        /**
         *  - ����������� ����������
         */
        $this->_applyFixtures(self::FIXTURE_TYPE_DELETE);

        // �������� ��������� ������� �����
        /**
         *  - ��������� ������ ����
         *  - ���������
         *  - ������ ���������
         */
    }
}