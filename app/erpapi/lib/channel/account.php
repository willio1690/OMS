<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_account extends erpapi_channel_abstract
{
    public $channel = array();

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $node_type node_type
     * @return mixed 返回值
     */
    public function init($node_id, $node_type)
    {

        return true;
    }
}
