<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_common
{
    
    
    /**
     * 获取ChannelId
     * @param mixed $node_type node_type
     * @return mixed 返回结果
     */
    public function getChannelId($node_type){
       $storeMdl = app::get('o2o')->model('store');
       $stores = $storeMdl->db->selectrow("SELECT s.store_id,v.server_id FROM sdb_o2o_store as s LEFT JOIN sdb_o2o_server as v on s.server_id=v.server_id WHERE v.node_type='".$node_type."'");
       return $stores;
    }



}
