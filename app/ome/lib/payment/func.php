<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_payment_func{
    /**
     * sync_payments
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function sync_payments($shop_id){
        $shopObj = app::get('ome')->model('shop');
        $cfgObj = app::get('ome')->model('payment_cfg');
        $payShopObj = app::get('ome')->model('payment_shop');
        $shop = $shopObj->dump($shop_id);

        $type = array(
            'taobao' => array(
                array(
                'bn' => 'alipaytrad',
                'name' => '支付宝担保交易',
                'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                ),
            ),
            'paipai' => array(
                array(
                  'bn' => 'tenpaytrad',
                  'name' => '财付通担保交易',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                ),
            ),
            'qq_buy' => array(
                array(
                  'bn' => 'tenpaytrad',
                  'name' => '财付通担保交易',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                ),
            ),
            '360buy' => array(
                array(
                  'bn' => 'online',
                  'name' => '线上支付',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                ),
            ),
            'yihaodian' => array(
                array(
                  'bn' => 'online',
                  'name' => '线上支付',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                )
            ),
            'dangdang' => array(
                array(
                  'bn' => 'online',
                  'name' => '线上支付',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                )
            ),
            'amazon' => array(
                array(
                  'bn' => 'online',
                  'name' => '线上支付',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                )
            ),
            'yintai' => array(
                array(
                  'bn' => 'online',
                  'name' => '线上支付',
                  'pay_type'=>'online',
                ),
                array(
                  'bn' => 'offline',
                  'name' => '线下支付',
                  'pay_type'=>'offline',
                ),
            ),
            'icbc' => array(
                array(
                        'bn' => 'online',
                        'name' => '线上支付',
                        'pay_type'=>'online',
                ),
                array(
                        'bn' => 'offline',
                        'name' => '线下支付',
                        'pay_type'=>'offline',
                )
            ),
            'mogujie' => array(
                    array(
                            'bn' => 'online',
                            'name' => '线上支付',
                            'pay_type'=>'online',
                    ),
                    array(
                            'bn' => 'offline',
                            'name' => '线下支付',
                            'pay_type'=>'offline',
                    )
            ), 
            'gome' => array(
                    array(
                            'bn' => 'online',
                            'name' => '线上支付',
                            'pay_type'=>'online',
                    ),
                    array(
                            'bn' => 'offline',
                            'name' => '线下支付',
                            'pay_type'=>'offline',
                    )
            ),
            'wx' => array(
                    array(
                            'bn' => 'online',
                            'name' => '线上支付',
                            'pay_type'=>'online',
                    ),
                    array(
                            'bn' => 'offline',
                            'name' => '线下支付',
                            'pay_type'=>'offline',
                    )
            ),
            'beibei' => array(
                array(
                    'bn' => 'online',
                    'name' => '线上支付',
                    'pay_type'=>'online',
                ),
                array(
                    'bn' => 'offline',
                    'name' => '线下支付',
                    'pay_type'=>'offline',
                )
            ),
        );
        if(isset($shop['shop_type']) && in_array($shop['shop_type'],array_keys($type))){
            foreach($type[$shop['shop_type']] as $v){
                $pay_bn   = $v['bn'];
                $pay_name = $v['name'];
                $pay_type = $v['pay_type'];

                $payment = $cfgObj->dump(array('pay_bn'=>$pay_bn), 'id,custom_name,pay_type');
                $cfgSdf = array(
                    'custom_name' => $pay_name,
                    'pay_bn' => $pay_bn,
                    'pay_type' => $pay_type,
                );
                $payShopSdf = array(
                    'pay_bn' => $pay_bn,
                    'shop_id' => $shop_id,
                );

                if(!empty($payment['id'])){
                    $cfgObj->update($cfgSdf,array('id'=>$payment['id']));
                }else{
                    $cfgObj->insert($cfgSdf);
                }
                $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn,'shop_id' => $shop_id), 'pay_bn,shop_id');
                if(!empty($payShop)){
                }else{
                    $payShopObj->insert($payShopSdf);
                }
            }
            return true;
        }else{
          
             kernel::single('erpapi_router_request')->set('shop',$shop_id)->paymenthod_getpaymethod(null);
        }
        return true;
    }

    /**
     * del_payments
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function del_payments($shop_id){
        $shopObj = app::get('ome')->model('shop');
        $cfgObj = app::get('ome')->model('payment_cfg');
        $payShopObj = app::get('ome')->model('payment_shop');

        $payShops = $payShopObj->getList('*',array('shop_id'=>$shop_id));
        $pay_bn = '';
        foreach($payShops as $payShop){
            $pay_bn = $payShop['pay_bn'];
            if($pay_bn){
                $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                    $cfgObj->delete(array('pay_bn'=>$pay_bn));
                }
            }
        }
        return true;
    }
}