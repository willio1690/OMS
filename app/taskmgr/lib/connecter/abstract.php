<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 数据获取对像基类
 *
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */

abstract class taskmgr_connecter_abstract {

    /**
     * 初始化数据访问对像
     *
     * @param string $task 任务标识
     * @return void
     */
    public function load($task, $config) {
    
        $this->connect($config);
    }
}