<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_yilianyun extends erpapi_channel_abstract
{
    public $channel;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $node_type node_type
     * @return mixed 返回值
     */
    public function init($node_id, $node_type)
    {

        $this->__adapter  = '';
        $this->__platform = '';

        return true;
    }
}