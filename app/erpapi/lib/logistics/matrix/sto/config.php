<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 应用级参数定义
 */
class erpapi_logistics_matrix_sto_config extends erpapi_logistics_matrix_config{
    protected $_to_node_id = '1064384233';
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params){
        $stoAccount = explode('|||',$this->__channelObj->channel['shop_id']);
        $stoObj = kernel::single('logisticsmanager_waybill_sto');
        $query_params = array(
            'cusname' => $stoAccount[0], //客户号
            'cusite' => $stoAccount[1], //网点名称
            'cuspwd'=>$stoAccount[2], //网点密码
            'businessType' => $stoObj->getbusinessType($this->__channelObj->channel['logistics_code']), //单据类型
            'to_node_id' => $this->_to_node_id,
            'node_type' => 'sto',
        );
        $pqp = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }
}