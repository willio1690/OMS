<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_shop_response_abstract
{
    public $__channelObj;
    
    public $__apilog;

    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */
    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }

    /**
     * 去首尾空格
     *
     * @param Array
     * @return Array
     * @author 
     **/
    static function trim(&$arr)
    {        
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }

    /**
     * 过滤空
     *
     * @return void
     * @author 
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }

    /**
     * 比较数组值
     *
     * @return void
     * @author 
     **/
    public function comp_array_value($a,$b)
    {
        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1 ;
    }

    /**
     * 支付方式获取
     * @param String $pay_bn 支付方式编号
     * @param String $shop_type 店铺类型
     * @return Array 支付方式信息
     */
    final public function get_payment($pay_bn,$shop_type=''){
        $default_cfg = array(
            'taobao' => 'alipaytrad',
            'paipai' => 'tenpaytrad',
        );
        if (!$pay_bn) $pay_bn = $default_cfg[$shop_type] ? $default_cfg[$shop_type] : 'online';
        $cfgObj = app::get('ome')->model('payment_cfg');
        $payment_cfg = $cfgObj->getList('*',array('pay_bn'=>$pay_bn),0,1);
        if ($payment_cfg) return $payment_cfg[0];
        switch ($pay_bn) {
            case 'deposit':
                $payment_cfg = array(
                    'custom_name' => '预存款',
                    'pay_bn'      => 'deposit',
                    'pay_type'    => 'deposit',
                );
                break;

            default:
                $payment_cfg = array(
                    'custom_name' => '线上支付',
                    'pay_bn'      => 'online',
                    'pay_type'    => 'online',
                );
                break;
        }
        $row = $cfgObj->getList('id',array('pay_bn'=>$payment_cfg['pay_bn']),0,1);
        if ($payment_cfg && !$row){
            $cfgObj->save($payment_cfg);
        } else {
            $payment_cfg['id'] = $row[0]['id'];
        }
        return $payment_cfg;
    }

    final protected function getOrder($field, $shopId, $orderBn, $source = '') {
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList($field, array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
        $archiveOrder = app::get('archive')->model('orders')->getList('order_id,status,process_status,ship_status,pay_status,payed,pay_bn,member_id,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile,is_protect,is_cod,source,order_type', array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
        if ($archiveOrder){
            $archiveOrder[0]['tran_type'] = 'archive';
            $tgOrder = $archiveOrder;
        }
        if (!$tgOrder) {
            $orderRsp = kernel::single('erpapi_router_request')->set('shop',$shopId)->order_get_order_detial($orderBn);
            if ($orderRsp['rsp'] == 'succ') {

                // 售后单拉，如果订单已经完成，重新打开，下载订单，暂时屏蔽
                // if ($source == 'aftersale' && $orderRsp['data']['trade']['status'] == 'TRADE_FINISHED') {
                //     $orderRsp['data']['trade']['status'] = 'TRADE_ACTIVE';
                // }

                if ($source == 'aftersale' && $orderRsp['data']['trade']['status'] == 'TRADE_CLOSED') {
                    return 0;// 售后单拉 如果订单已取消，不删除refund_no_order信息 防止队列延时
                }

                $msg = '';
                $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shopId,$msg);
                if ($rs) {
                    $tgOrder = $orderModel->getList($field, array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
                }
            }
        }
        
        //brush特殊订单
        if($tgOrder[0]['order_type'] == 'brush'){
            return false;
        }
        
        return $tgOrder ? $tgOrder[0] : false;
    }

    protected function _dealRefundNoOrder($sdf) {
        $filter = array(
            'order_bn' => $sdf['order_bn'],
            'shop_id' => $sdf['shop_id'],
            'refund_bn' => $sdf['refund_bn']
        );
        $rnoModel = app::get('ome')->model('refund_no_order');
        $rs = $rnoModel->getList('id', $filter);
        if(!$rs) {
            $refundNoOrder = array(
                'order_bn' => $sdf['order_bn'],
                'shop_id' => $sdf['shop_id'],
                'refund_bn' => $sdf['refund_bn'],
                'status' => $sdf['status'],
                'sdf' => serialize($sdf)
            );
            $rnoModel->insert($refundNoOrder);
        }
    }
}