<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_rpc_response_channel
{
    function callback($result){
        $channel_id = $result['channel_id'];
        $node_type  = $_POST['node_type'];
        $node_id    = $_POST['node_id'];
        $user_nick  = $_POST['user_nick'];
        $shop_title = $_POST['shop_title'];
        $vender_id  = $_POST['vender_id'];
        $unikey     = $_POST['unikey'];
        $status       = $_POST['status'];

        if (!$channel_id || !$node_id) die('0');

        $channelModel = app::get('logisticsmanager')->model('channel');

        $channel = $channelModel->dump($channel_id);

        if (!$channel) die('0');

        $addon = $channel['addon'] ? $channel['addon'] : array();
        if ($status == 'bind') {
            $addon['user_nick'] = $user_nick;

            $data = array(
                'node_id'     =>$node_id,
                'bind_status' =>'true',
                'addon'       => $addon
            );

            $channelModel->update($data,array('channel_id'=>$channel_id));
        } elseif ($status == 'unbind') {
            unset($addon['user_nick']);
            $channelModel->update(array('node_id'=>null,'bind_status'=>'false','addon'=>$addon),array('channel_id'=>$channel_id));
        }

        die('1');
    }
}