<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_preprocess_outstorage {
    /**
     * 处理vjia等出库失败订单
     */
    public function process($order_id,&$msg){
        if(!$order_id){
            $msg = '缺少处理参数';
            return false;
        }

        $outstorageObj = app::get('ome')->model('order_outstorage');
        $outstorage = $outstorageObj->dump(array('order_id'=>$order_id),'order_id');
        if(is_array($outstorage) && !empty($outstorage)) {
            $orderObj = app::get('ome')->model('orders');
            $orderInfo = $orderObj->dump(array('order_id'=>$order_id),'order_id,order_bn,shop_id,process_status,order_type');
            
            //补发订单
            if ($orderInfo['order_type'] == 'bufa') {
                $msg = '补发订单不需要执行出库';
                return true;
            }
            
            // $rpcData = array();
            // $rpcData['tid'] = $orderInfo['order_bn'];
            // $rpcData['order_id'] = $orderInfo['order_id'];
            // $rpcData['company_code'] = 'OTHER';
            // $rpcData['company_name'] = '客户自提';
            // $rpcData['logistics_no'] = sprintf('%u',crc32(uniqid()));


            $result = kernel::single('erpapi_router_request')
                        ->set('shop', $orderInfo['shop_id'])
                        ->delivery_outstorage($orderInfo);

            if ($result['rsp'] == 'fail' && $orderInfo['process_status'] == 'unconfirmed') {
                $abnormalObj = app::get('ome')->model('abnormal');
                $abnormal = $abnormalObj->dump(array("order_id"=>$order_id));

                $msg = "出库失败(".$result['msg'].")，system设置为异常订单，请检查前端订单状态！";
                $data = array(
                    'abnormal_id'=>$abnormal['abnormal_id'],
                    'order_id'=>$order_id,
                    'is_done'=>'false',
                    'abnormal_memo'=>$msg,
                    'abnormal_type_id' => 0,
                );
                $orderObj->set_abnormal($data);
            }
        }
        return true;
    }
}
