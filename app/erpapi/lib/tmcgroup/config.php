<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 天猫订阅消息接口配置类
 * wangjianjun update 20181107
 * @version 0.1
 */
class erpapi_tmcgroup_config extends erpapi_config
{
    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author 
     **/
    public function get_query_params($method, $params){
        $query_params = array(
            'app_id'       => 'ecos.ome',
            'method'       => $method,
            'date'         => date('Y-m-d H:i:s'),
            'format'       => 'json',
            'certi_id'     => base_certificate::certi_id(),
            'v'            => '1',
            'from_node_id' => base_shopnode::node_id('ome'),
            'to_node_id'   => $this->__channelObj->tmcgroup['node_id'],
        );
        return $query_params;
    }
    
    private $__global_whitelist = array(
        EINVOICE_ADD_TMC_GROUP,
        EINVOICE_DEL_TMC_GROUP,
    );

}