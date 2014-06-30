<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\Tests\AbstractCase;

class SimpleCase extends AbstractCase{

    public function name() {
        return '�������� �����';
    }

    public function description() {
        return '������������� ����� ��������� ������������';
    }

    public function testError() {
//        $this->assertTrue(false);
    }

    public function testSuccess() {
        $this->assertTrue(true);
    }
}