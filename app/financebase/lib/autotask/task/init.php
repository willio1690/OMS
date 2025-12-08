<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_autotask_task_init{
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
	{
		$this->oFunc = kernel::single('financebase_func');
        $this->oQueue = app::get('financebase')->model('queue');
	}

    /**
     * __deconstruct
     * @return mixed 返回值
     */
    public function __deconstruct()
	{
		unset($this->oFunc,$this->oQueue);
	}
}