<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店发货单响应处理类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_response_process_delivery
{
    /**
     * 发货单
     * @param Array $params=array(
     *                  'status'=>@状态@ delivery 
     *                  'delivery_bn'=>@发货单号@
     *                  'out_delivery_bn'=>@外部发货单号@
     *                  'logi_no'=>@运单号@
     *                  'delivery_time'=>@发货时间@
     *                  'weight'=>@重量@
     *                  'delivery_cost_actual'=>@物流费@
     *                  'logi_id'=>@物流公司编码@
     *                  ===================================
     *                  'status'=>print,
     *                  'delivery_bn'=>@发货单号@
     *                  'stock_status'=>@备货单打印状态@
     *                  'deliv_status'=>@发货单打印状态@
     *                  'expre_status'=>@快递单打印状态@
     *                  ===================================
     *                  'status'=>check
     *                  'delivery_bn'=>@发货单号@
     *                  ===================================
     *                  'status'=>cancel
     *                  'delivery_bn'=>@发货单号@
     *                  'memo'=>@备注@
     *                  ===================================
     *                  'status'=>update
     *                  'delivery_bn'=>@发货单号@
     *                  'action'=>updateDetail|addLogiNo
     *                  
     *
     *              )
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        if($params['operate_time']) $params['delivery_time'] = $params['operate_time'];
        if(!empty($params['bill_logi_no']) && is_array($params['bill_logi_no'])) {
            $dliBill = app::get('ome')->model('delivery_bill');
            foreach($params['bill_logi_no'] as $val) {
                $bill = array();
                $bill['status'] = $params['status'] == 'delivery' ? 1 : ($params['status'] == 'cancel' ? 2 : 0);
                $bill['logi_no'] = $val;
                $delivery_data = app::get('ome')->model('delivery')->dump(array('delivery_bn'=>$params['delivery_bn']),'status,delivery_id');
                $bill['delivery_id'] = $delivery_data['delivery_id'];
                $hadBill = $dliBill->dump(array('delivery_id'=>$bill['delivery_id'],'logi_no'=>$bill['logi_no']), 'log_id');
                if(empty($hadBill)) {
                    $bill['create_time'] = strtotime($params['operate_time']);
                } else {
                    $bill['log_id'] = $hadBill['log_id'];
                }
                $bill['delivery_time'] = strtotime($params['operate_time']);
                $dliBill->save($bill);
            }
        }
        return kernel::single('ome_event_receive_delivery')->update($params);
    }
}
