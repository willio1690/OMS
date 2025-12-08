<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class channel_task{

    function post_install($options){
        // 插入默认奇门渠道
        $this->_insert_qimen_channel();
    }

    /**
     * 插入默认奇门渠道
     */
    private function _insert_qimen_channel(){
        $channelMdl = app::get('channel')->model('channel');
        // 检查是否已存在
        $exists = $channelMdl->getList('channel_id', array('channel_type' => 'qimen'), 0, 1);
        if (empty($exists)) {
            $data = array(
                'channel_bn' => 'qimen-jst-erp',
                'channel_name' => '奇门聚石塔内外互通',
                'channel_type' => 'qimen',
                'active' => 'true',
                'disabled' => 'false',
                'node_type' => 'qimen-jst-erp',
            );
            $channelMdl->insert($data);
        }
    }
}
