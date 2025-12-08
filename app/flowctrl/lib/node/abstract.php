<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 流程控制事件节点抽象类
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
abstract class flowctrl_node_abstract{

    public function getConfig(){
        return $this->__Config;
    }

    public function showConfigDesc($cnf){
        return $this->processModeToString($cnf);
    }
}