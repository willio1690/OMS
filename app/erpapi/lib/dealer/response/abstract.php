<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_dealer_response_abstract
{
    public $__channelObj;
    
    public $__apilog;
    
    public $_operationSel = '';
    
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
     * @return void
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
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }

    /**
     * 比较数组值
     *
     * @return void
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
     *
     * @param String $pay_bn 支付方式编号
     * @param String $shop_type 店铺类型
     * @return Array 支付方式信息
     */
    final public function get_payment($pay_bn,$shop_type='')
    {
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

    final protected function getOrder($field, $shopId, $orderBn)
    {
        
        $orderModel = app::get('dealer')->model('platform_orders');
        $tgOrder = $orderModel->getList($field, array('plat_order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
      
        return $tgOrder ? $tgOrder[0] : false;
    }

    protected function _dealRefundNoOrder($sdf)
    {
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
    
    /**
     * 获取平台名称
     * @todo：taobao淘宝、tmall天猫、360buy京东、luban抖音
     * 
     * @return string
     */
    public function getPlatformName()
    {
        //平台列表
        $shopTypes = ome_shop_type::get_shop_type();
        
        //平台编码
        $shop_type = $this->__channelObj->channel['shop_type'];
        
        return $shopTypes[$shop_type];
    }
    
    /**
     * 前置处理平台数据
     * @todo：去除抖音平台订单A字母;
     * 
     * @return string
     */
    public function preFormatData($sdf)
    {
        //replace
        if($this->__channelObj->channel['shop_type'] == 'luban'){
            if(substr($sdf['order_bn'], -1) === 'A'){
                $sdf['order_bn'] = substr($sdf['order_bn'], 0, -1);
            }
        }
        
        //经销订单号
        $sdf['plat_order_bn'] = $sdf['order_bn'];
        
        return $sdf;
    }
}