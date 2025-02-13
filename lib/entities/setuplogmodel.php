<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

class SetupLogModel extends BaseEntity {
    public
        $id, $userId;
    /**
     * @var \DateTime
     */
    public $date;

    public function __construct() {
        $this->date = new \DateTime();
    }

    static protected function map() {
        return array(
            'id' => 'ID',
            'date' => 'DATE',
            'userId' => 'USER_ID'
        );
    }

    static protected function gatewayClass() {
        return SetupLogTable::className();
    }

    static protected function modifyFromDb($data) {
        if ($data['date'] instanceof DateTime) {
            $timestamp = $data['date']->getTimestamp();
            $data['date'] = new \DateTime();
            $data['date']->setTimestamp($timestamp);
        } else {
            $data['date']= new \DateTime($data['date']);
        }
        return $data;
    }

    static protected function modifyToDb($data) {
        $data['date'] && $data['date'] instanceof \DateTime && $data['date'] = DateTime::createFromPhp($data['date']);
        return $data;
    }

    /**
     * @return AppliedChangesLogModel[]
     */
    public function getAppliedLogs() {
        return AppliedChangesLogModel::find(array(
            'filter' => array(
                '=setupLogId' => $this->id
            )
        ));
    }

    /**
     * @return array
     */
    public function getUserData() {
        return UserTable::getById($this->userId)->fetch();
    }

    public function shortUserInfo() {
        $data = $this->getUserData();
        return $data['NAME'].' '.$data['LAST_NAME'].' ['.$data['ID'].']';
    }
}