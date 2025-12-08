<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 指定快递(店小蜜)
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_response_express extends erpapi_shop_response_abstract
{
    /**
     * 接收参数
     */

    public $_sdf = array();
    
    /**
     * 获取指定快递可用物流公司
     */
    public function getcorp($params=array()){
        $corpObj = app::get('ome')->model('dly_corp');
        
        $this->__apilog['title'] = '获取指定快递对应物流公司';
        
        //按权重优先级
        $sql = "SELECT corp_id, type, name FROM sdb_ome_dly_corp WHERE disabled='false' AND d_type=1 GROUP BY type ORDER BY weight DESC, corp_id DESC";
        $corpList = $corpObj->db->select($sql);
        if(empty($corpList)){
            $this->__apilog['result']['msg'] = '没有可使用的物流公司!';
            return false;
        }
        
        return $corpList;
    }
    
    /**
     * 指定快递
     * 
     * @param array $params
     * @return array
     */
    public function assign($params){
        $this->__apilog['title'] = '指定快递';
        
        //format params
        $params = $this->_returnParams($params);
        if(empty($params)){
            $this->__apilog['result']['msg'] = '指定快递: 该平台不支持订单指定快递';
            return false;
        }
        
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = $params;
        
        if(empty($params['order_bn'])) {
            $this->__apilog['result']['msg'] = '指定快递: 没有可处理的订单!';
            return false;
        }
        
        //检查订单
        $orderObj = app::get('ome')->model('orders');
        $this->_sdf = $orderObj->dump(array('order_bn'=>$params['order_bn']), 'order_id, order_bn, process_status, status, ship_status');
        if(empty($this->_sdf)){
            $this->__apilog['result']['msg'] = '指定快递: 订单不存在,请检查!';
            return false;
        }
        
        if($this->_sdf['status'] == 'dead'){
            $this->__apilog['result']['msg'] = '指定快递: 订单已作废,不能指定快递!';
            return false;
        }
        
        if(!in_array($this->_sdf['ship_status'], array('0', '2'))){
            $this->__apilog['result']['msg'] = '指定快递: 订单已发货,不能指定快递!';
            return false;
        }
        
        $this->_sdf = array_merge($this->_sdf, $params);
        
        return $this->_sdf;
    }
    
    /**
     * 获取数据
     * 
     * @param array $params
     * @return array:
     */
    protected function _returnParams($params) {
        return array();
    }
    
    /**
     * 格式化参数
     * 
     * @param array $params
     * @return array:
     */
    protected function _formatParams($params) {
        
        //订单号
        $order_bn = $params['tid'];
        
        //物流公司编码
        $express_code = strtoupper($params['company_code']);
        
        //组织数据
        $sdf = array(
                'order_bn' => $order_bn,
                'express_code' => $express_code,
        );
        
        return $sdf;
    }
}
