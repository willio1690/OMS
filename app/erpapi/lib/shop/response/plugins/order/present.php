<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Date: 2025/2/5
 * @Describe: 送礼订单插件生成退款申请单和退款单
 */
class erpapi_shop_response_plugins_order_present extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $refundApplySdf = array ();

        //送礼订单且有退运费金额
        if ($platform->_ordersdf['extend_field']['present']['refund_freight'] > '0' && $platform->_ordersdf['extend_field']['present']['is_present'] == '1') {
            $applyBn = $platform->_ordersdf['order_bn'] . 'postfee';

            $refundApplyModel = app::get('ome')->model('refund_apply');

            $refund_money = $platform->_ordersdf['extend_field']['present']['refund_freight'];

            // 判断是否有申请中的
            $has = $refundApplyModel->getList('apply_id', array ('order_id' => $platform->_tgOrder['order_id'], 'refund_apply_bn' => $applyBn), 0, 1);
            if ($has) {
                return array ();
            }

            $create_time = $platform->_ordersdf['lastmodify'] ? kernel::single('ome_func')->date2time($platform->_ordersdf['lastmodify']) : time();

            $refundApplySdf = array (
                'refund_apply_bn' => $applyBn,
                'pay_type'        => 'online',
                'money'           => $refund_money,//退款金额
                'refund_money'    => $refund_money,//退款金额
                'bcmoney'         => 0,//补偿费用
                'refunded'        => $refund_money,
                'memo'            => '送礼订单退运费',
                'create_time'     => $create_time,
                'status'          => '2',
                'shop_id'         => $platform->__channelObj->channel['shop_id'],
                'source'          => 'local',//来源：本地新建
                'refund_refer'    => '0',//退款申请来源：普通流程产生的退款申请
            );
        }


        return $refundApplySdf;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id, $refundapply)
    {
        //创建完成退款申请单和退款单
        if ($refundapply) {
            $refundapply['order_id'] = $order_id;
            list($res, $error_msg) = kernel::single('ome_refund_apply')->createFinishRefundApply($refundapply, false);

            $logModel = app::get('ome')->model('operation_log');
            $logModel->write_log('order_edit@ome', $order_id, '送礼订单退运费' . $error_msg);
        }
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $refundapply) {}
}