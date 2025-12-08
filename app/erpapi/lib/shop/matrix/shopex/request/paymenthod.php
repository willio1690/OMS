<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_request_paymenthod  extends erpapi_shop_request_paymenthod
{
    const _APP_NAME = 'ome';
    /**
     * 获取店铺支付方式
     *
     * @return void
     * @author 
     **/    
    public function getpaymethod()
    {
        $data = array('shop_id' => $this->__channelObj->channel['shop_id'],'obj_bn'=>$this->__channelObj->channel['shop_id']);
        $params= array();
        $callback = array(
            'class' => get_class($this),
            'method' => 'get_paymethod_callback',
            'params' => $data
        );
        

        $title = '同步店铺('.$this->__channelObj->channel['name'].')的支付方式';
        
        $this->__caller->call(SHOP_PAYMETHOD_RPC,$params,$callback,$title,10,$this->__channelObj->channel['shop_id']);
    }

    public function get_paymethod_callback($response, $callback_params){
        
        //$status = $result->get_status();
  
         if($response['rsp'] == 'succ'){
            $cfgObj = app::get(self::_APP_NAME)->model('payment_cfg');
            $payShopObj = app::get(self::_APP_NAME)->model('payment_shop');
        
            $msg_id = $response['msg_id'];
            $log_id = $callback_params['log_id'];
            $shop_id = $callback_params['shop_id'];
            
            $shopObj = app::get(self::_APP_NAME)->model('shop');
            
            $rsp      = $response['rsp'];
            $payments = @json_decode($response['data'],true);
            $shops_info =  $shopObj->getList('node_type',array('shop_id'=>$shop_id));

            if( (is_array($payments) && count($payments)>0 && $shop_id) || ($rsp == 'succ') ) {
                $pay_bn = '';
                foreach((array)$payments as $payment){
                    if( $shops_info[0]['node_type'] == 'ecshop_b2c'){
                        $payment['pay_bn'] = $payment['payment_bn'];
                        $payment['custom_name'] = $payment['payment_type'];
                    }elseif ( $shops_info[0]['node_type'] == 'shopex_fy'){
                        $payment['custom_name'] = $payment['payment_type'];
                    }
                    $pay_bn = $payment['pay_bn'];
    
                    if(isset($pay_bn) && $pay_bn){
                        $pay_bns[] = $payment['pay_bn'];
                        $pay_type = $payment['pay_type'];

                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }

                        $cfgSdf = array(
                            'custom_name' => $payment['custom_name'],
                            'pay_bn' => $pay_bn,
                            'pay_type' => $pay_type,
                        );
                        $payShopSdf = array(
                            'pay_bn' => $pay_bn,
                            'shop_id' => $shop_id,
                        );

                        $cfgObj->insert($cfgSdf);
                        $payShopObj->insert($payShopSdf);
                    }
                }

                $payShops = $payShopObj->getList('*',array('shop_id'=>$shop_id));
                $pay_bn = '';
                foreach($payShops as $payShop){
                    $pay_bn = $payShop['pay_bn'];
                    if($pay_bn && !in_array($pay_bn,$pay_bns)){
                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }
                    }
                }
            }
        }
        return $this->callback($response, $callback_params);
    }    
}