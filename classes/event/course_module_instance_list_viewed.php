<?php

namespace mod_competvet\event;

class course_module_instance_list_viewed extends \core\event\course_viewed {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'competvet';
    }
}
