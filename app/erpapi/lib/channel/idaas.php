<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author chenping@shopex.cn 2017/5/24
 * @describe 无需授权接口
 */
class erpapi_channel_idaas extends erpapi_channel_abstract
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
