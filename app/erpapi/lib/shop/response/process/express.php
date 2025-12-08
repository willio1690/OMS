<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 指定快递
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_response_process_express extends erpapi_shop_response_abstract
{
    /**
     * 获取指定快递可用物流公司
     */

    public function getcorp($corpList){
        
        $data = array();
        foreach ($corpList as $key => $val){
            $data[] = array('type'=>$val['type'], 'name'=>$val['name']);
        }
        
        //return
        return array('rsp'=>'succ', 'data'=>json_encode($data), 'msg'=>'获取物流公司成功!');
    }
    
    /**
     * 指定快递
     * 
     * @param array $order
     * @return array
     */
    public function assign($order)
    {
        $express_code = $order['express_code'];
        
        //已生成发货单&&未发货时,叫回发货单
        if(in_array($order['process_status'], array('splitting', 'splited'))){
            //打回未发货的发货单
            $orderObj = app::get('ome')->model('orders');
            $isCancel = $orderObj->rebackDeliveryByOrderId($order['order_id'], true);
            if(!$isCancel){
                return array('rsp'=>'fail', 'msg'=>'指定快递: 订单撤消未发货的发货单失败!');
            }
        }
        
        //判断物流公司是否存在
        $corpObj = app::get('ome')->model('dly_corp');
        $corpInfo = $corpObj->dump(array('type'=>$express_code, 'disabled'=>'false'), 'corp_id,name');
        if(empty($corpInfo)){
            return array('rsp'=>'fail', 'msg'=>'指定快递: OMS系统中不存在指定的物流公司!');
        }
        
        $dlyList = $corpObj->corp_default();
        $express_name = $dlyList[$express_code]['name']; //物流公司名称
        
        //保存指定快递信息
        $extendObj = app::get('ome')->model('order_extend');
        $data = array(
                'order_id' => $order['order_id'],
                'assign_express_code' => $express_code,
        );
        $extendObj->save($data);
        
        //日志
        $memo = '指定快递: 买家指定快递 '. $express_name .'('. $express_code .')';
        app::get('ome')->model('operation_log')->write_log('order_modify@ome', $order['order_id'], $memo);
        
        //return
        return array('rsp'=>'succ','msg'=>'指定快递: 订单更新为指定快递成功!');
    }
}
