<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 导入分派任务
 */
class taskmgr_task_finance_assign extends omecsv_autotask_task_init {
    
    protected $_process_id = 'queue_id';
    
    protected $_gctime = 60;
    
    protected $_timeout = 180;
}