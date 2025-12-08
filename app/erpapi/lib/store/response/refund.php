<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_refund extends erpapi_store_response_abstract
{
    static public $refund_status = array(
        'APPLY' =>  '0',
        'SUCC'  =>  'succ',
        'CANCEL'=>  '3',
      
    );
    
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){
        $this->__apilog['title'] = $this->__channelObj->store['name'] .'('.$params['store_bn'].')退款申请单' . $params['tid'].'|'.microtime(true);
        
        $this->__apilog['original_bn'] = $params['tid'];
        $store_bn = $params['store_bn'];

        if (empty($store_bn)) {

            $this->__apilog['result']['msg'] = "下单门店编码不可以为空";
            return false;
        }

        $refund_type = $params['refund_type'];
        if(empty($refund_type) || !in_array($refund_type,array('refund','apply'))){
            $this->__apilog['result']['msg'] = "refund_type不可以为空或者必须为refund或apply";
            return false;
        }
        if(!in_array($params['status'],array_keys(self::$refund_status))){
            $this->__apilog['result']['msg'] = $params['status'].":状态不处理";
            return false;
        }

        $shops_detail = app::get('ome')->model('shop')->dump(array('shop_bn'=>$store_bn));

        if (!$shops_detail){
            $this->__apilog['result']['msg'] = $store_bn.":门店不存在";
            return false;
        }
        $params['order_bn'] = $params['tid'];
        $this->_dealSavePos($params);
        $data = $params;
        $data['status'] = self::$refund_status[strtoupper($params['status'])];
       
        $data['pay_type'] = 'online';
        $data['shop_type']  = $shops_detail['node_type'];
        $data['node_id']    = $shops_detail['node_id'];

        return $data;
    }
    
    /**
     * _dealSavePos
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _dealSavePos($params){

        $refundMdl = app::get('pos')->model('refund');
        $refunds = $refundMdl->db_dump(array('refund_bn'=>$params['refund_bn'],'store_bn'=>$params['store_bn']),'id');
        $refundData = [
            'refund_bn'     =>  $params['refund_bn'],
            'store_bn'      =>  $params['store_bn'],
            'order_bn'      =>  $params['order_bn'],
            'oid'           =>  $params['oid'],
            'money'         =>  $params['money'],
            't_begin'       =>  strtotime($params['t_begin']),
            'refund_type'   =>  $params['refund_type'],
            'params'        =>  json_encode($params),

        ];

        if($refunds){
            $filter = array('id'=>$refunds['id']);
            $id = $refundMdl->update($refundData,$filter);
        }else{
            $id = $refundMdl->insert($refundData);
        }
        
    }
}

?>