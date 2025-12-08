<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author chenping 2016/8/22
 * @describe alipay/JD钱包
 */
class erpapi_channel_ipay extends erpapi_channel_abstract
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
        $channelMdl = app::get('channel')->model('channel');
        $filter = $channel_id ? array('channel_id'=>$channel_id) : array('node_id'=>$node_id);
        $channel = $channelMdl->db_dump($filter);

        if (!$channel || $channel['channel_type'] != 'ipay') return false;

        $this->__platform = $channel['node_type'];
        $this->__adapter  = 'matrix';

        $this->channel = $channel;

        return true;
    }
}