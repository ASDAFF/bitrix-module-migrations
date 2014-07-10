<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;
use WS\Migrations\Tests\Mocks\ReferenceController;

class UpdateTestCase extends AbstractCase {

    public function name() {
        return '���������� ���������';
    }

    public function description() {
        return '������������ ���������� ��������� �������� ���������';
    }
}