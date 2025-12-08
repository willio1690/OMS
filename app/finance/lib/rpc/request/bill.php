<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 财务费用
*
* @category finance
* @package finance/lib/rpc/request
* @author chenping<chenping@shopex.cn>
* @version $Id: bill.php 2013-3-12 17:23Z
*/
class finance_rpc_request_bill{

    const _APP_NAME = 'ome';

    private $shop_id = NULL;

    /**
     * 店铺ID初始
     *
     * @param String $shop_id 店铺ID
     * @return void
     * @author 
     **/

    public function setShopId($shop_id)
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function __call($method,$args)
    {
        $rs = array('rsp'=>'fail','msg'=>'no method','msg_code'=>'','msg_id'=>'','data'=>'');

        if (!$this->shop_id) {
            $rs['msg'] = 'set shop id first';    
            return $rs;
        }

        $shop = app::get(self::_APP_NAME)->model('shop')->dump($this->shop_id);
        if (!$shop['node_type']) {
            $rs['msg'] = 'no shop type';
            return $rs;
        }

        try {
            $class_name = sprintf('finance_rpc_request_bill_%s',$shop['node_type']);

            if (class_exists($class_name)) {
                $platform = kernel::single($class_name);
                if ($platform instanceof finance_rpc_request_bill_abstract && method_exists($platform,$method)) {
                    $platform->setShop($shop);

                    return call_user_func_array(array($platform,$method), $args);
                }
            }
        } catch (Exception $e) {
            $rs['msg'] = 'no file';
            return $rs;
        }

        return $rs;
    }
}