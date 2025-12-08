<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_ticket extends erpapi_channel_abstract
{

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */
    public function init($node_id, $channel_id)
    {
        $channelMdl = app::get('channel')->model('channel');

        $filter                 = $channel_id ? array('channel_id' => $channel_id) : array('node_id' => $node_id);
        $filter['channel_type'] = 'ticket';
        $channel                    = $channelMdl->dump($filter);

        if (!$channel) {
            return false;
        }

        $channel['config'] = @unserialize($channel['config']);
        $this->__adapter = $channel['channel_adapter'];
        $this->__platform = $channel['node_type'];
        $this->channel = $channel;

        return true;
    }
}
