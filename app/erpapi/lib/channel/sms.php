<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016-01-19
 * @describe 短信发送接口
 */
class erpapi_channel_sms extends erpapi_channel_abstract
{
    public $channel;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$channel_id)
    {
        $param = unserialize($channel_id);
        if (!$param) { return false; }
        $this->__adapter = '';
        $this->__platform = '';
        $this->channel['account'] = $param;
        return true;
    }
}