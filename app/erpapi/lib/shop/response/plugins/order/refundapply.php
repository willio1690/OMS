<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016-03-11
 * @describe 店铺货品冻结库存
*/
class erpapi_shop_response_plugins_order_refundapply extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $refundApplySdf = array();

        if ($platform->_tgOrder['order_id']) {
            $refundApplyModel = app::get('ome')->model('refund_apply');

            $refund_money = 0;
            if ($platform->_ordersdf['payed'] > $platform->_ordersdf['total_amount']) {
                $refund_money = bcsub($platform->_ordersdf['payed'], $platform->_ordersdf['total_amount'],3);
            }

            if($refund_money <= 0) return array();

            // 判断是否有申请中的
            $has = $refundApplyModel->getList('apply_id',array('order_id'=>$platform->_tgOrder['order_id'],'status'=>array('0','1','2')),0,1);
            if ($has) {
                return array();
            }

            $create_time = $platform->_ordersdf['lastmodify'] ? kernel::single('ome_func')->date2time($platform->_ordersdf['lastmodify']) : time();
            $refundApplySdf = array(
                'order_id'        => $platform->_tgOrder['order_id'],
                'refund_apply_bn' => $refundApplyModel->gen_id(),
                'pay_type'        => 'online',
                'money'           => $refund_money,//退款金额
                'refund_money'    => $refund_money,//退款金额
                'bcmoney'         => 0,//补偿费用
                'refunded'        => '0',
                'memo'            => '订单编辑产生的退款申请',
                'create_time'     => $create_time,
                'status'          => '2',
                'shop_id'         => $platform->__channelObj->channel['shop_id'],
            );
        }


        return $refundApplySdf;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id,$refundapply)
    {
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$refundapply)
    {
        $refundapply['order_id']        = $order_id;
        $refundapply['source']          = 'local';//来源：本地新建
        $refundapply['refund_refer']    = '0';//退款申请来源：普通流程产生的退款申请
        
        //创建退款单
        $is_update_order    = false;//是否更新订单付款状态
        kernel::single('ome_refund_apply')->createRefundApply($refundapply, $is_update_order, $error_msg);
        
        $logModel = app::get('ome')->model('operation_log');
        $logModel->write_log('order_edit@ome',$order_id,'退款申请');
    }
}