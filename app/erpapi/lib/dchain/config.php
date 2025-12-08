<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/18
 * @Describe: 外部erp配置项
 */
class erpapi_dchain_config extends erpapi_config
{
    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */

    public function init(erpapi_channel_abstract $channel){
        $this->__whitelist = kernel::single('erpapi_dchain_whitelist')->getWhiteList($channel->channel['node_type']);
        return parent::init($channel);
    }
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
            'from_node_id' => base_shopnode::node_id('ome'),
            'to_node_id'   => $this->__channelObj->channel['node_id'],
            'node_type'    => $this->__channelObj->channel['node_type'],
        );

        return $query_params;
    }
}