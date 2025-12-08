<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_rpc_response_channel extends ome_rpc_response{
    /**
     * crm_callback
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function crm_callback($result){
        $channel_type = 'crm';//定义写死为crm类型
        $nodes = $_POST;
        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type'];
        $api_v = $nodes['api_v'];
        
        $filter = array('channel_type'=>$channel_type);
        
        $Obj_channel = kernel::single('channel_channel');
        #检查是否存在crm这条表记录
        $_rs = $Obj_channel->exists($channel_type);
        if ($status == 'bind'){
            $data = array('api_version'=>$api_v,'node_id'=>$node_id,'node_type'=>$node_type,'addon'=>$nodes);
            if($_rs){
                $Obj_channel->update($data, $filter);
            }else{
                $self_node_id = base_shopnode::node_id('ome');
                $data['channel_type'] = $channel_type;
                $data['channel_name'] = $channel_type.'_'.$self_node_id;
                $data['channel_bn'] = $channel_type.'_'.$self_node_id;
                $Obj_channel->insert($data);
            }
            die('1');
        }elseif ($status == 'unbind'){
            #解绑操作
            $Obj_channel->unbind($filter);
            die('1');
        }
        die('1');
    }
}
