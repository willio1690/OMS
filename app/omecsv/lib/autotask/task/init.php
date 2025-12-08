<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omecsv_autotask_task_init{
    public function __construct()
    {
        $this->oFunc = kernel::single('omecsv_func');
        $this->oQueue = app::get('omecsv')->model('queue');
    }
    
    public function __deconstruct()
    {
        unset($this->oFunc,$this->oQueue);
    }
}