<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;


use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class UpdateProcess extends BaseProcess {

    private $_beforeChangesSnapshots = array();

    public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix, AppliedChangesLogModel $log) {
        $data = $fix->getUpdateData();
        $id = $subjectHandler->getIdBySnapshot($data);
        $originalData = $subjectHandler->getSnapshot($id);

        $result = $subjectHandler->applyChanges($data);

        $log->description = $fix->getName().' - '.$id;
        $log->originalData = $originalData;
        $log->updateData = $data;

        return $result;
    }

    public function rollback(BaseSubjectHandler $subjectHandler, AppliedChangesLogModel $log) {
        return $subjectHandler->applySnapshot($log->originalData);
    }

    public function beforeChange(BaseSubjectHandler $subjectHandler, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_BEFORE_CHANGE_KEY, $data);
        $this->_beforeChangesSnapshots[$id] = $snapshot = $subjectHandler->getSnapshot($id);
    }

    public function afterChange(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_AFTER_CHANGE_KEY, $data);
        $originalData = $this->_beforeChangesSnapshots[$id];
        $actualData = $subjectHandler->getSnapshot($id);
        $data = $subjectHandler->analysisOfChanges($actualData, $this->_beforeChangesSnapshots[$id]);
        $fix
            ->setOriginalData($originalData)
            ->setUpdateData($data);
    }

    public function getName() {
        return $this->getLocalization()->getDataByPath('update');
    }
}