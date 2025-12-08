<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 应用级参数定义
 */
class erpapi_logistics_matrix_taobao_config extends erpapi_logistics_matrix_config{

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params){
        $shop = app::get('ome')->model('shop')->dump(array('shop_id'=>$this->__channelObj->channel['shop_id']), 'node_type,node_id');
        $query_params = array(
            'cp_code' => $this->__channelObj->channel['logistics_code'],
            'to_node_id' => $shop['node_id'],
            'node_type' => $shop['node_type'],
        );
        $pqp = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }

    /**
     * 获取_to_node_id
     * @return mixed 返回结果
     */
    public function get_to_node_id()
    {
        $shopId = $this->__channelObj->channel['shop_id'];
        $shop = app::get('ome')->model('shop')->dump(array('shop_id'=>$shopId), 'node_id');

        return $shop['node_id'];
    }
}