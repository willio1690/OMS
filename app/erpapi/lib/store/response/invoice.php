<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_invoice extends erpapi_store_response_abstract
{
    

    /**
     * 
     * @param  $params [参数] method store.invoice.invoice
     * @return array
     */
    public function add($params){
        $this->__apilog['title']       = $this->__channelObj->store['name'].'订单开票';
        $this->__apilog['original_bn'] = $params['tid'];

        if (empty($params['tid'])) {

            $this->__apilog['result']['msg'] = "订单号不可为空";
            return false;
        }
        $store_bn = $params['store_bn'];

        if (empty($store_bn)) {

            $this->__apilog['result']['msg'] = "下单门店编码不可以为空";
            return false;
        }

        $shops_detail = app::get('ome')->model('shop')->dump(array('shop_bn' => $store_bn));
        if (!$shops_detail) {
            $this->__apilog['result']['msg'] = $store_bn . ":门店不存在";
            return false;
        }
        //判断是否存在
        
        $data = array(
            'tid'           =>  $params['tid'],
            'invoice_kind'  =>  $params['invoice_kind'],
            'invoice_attr'  =>  $params['invoice_attr'],
            'company_title' =>  $params['company_title'],
            'register_no'   =>  $params['register_no'],
            'invoice_amount'=>  $params['invoice_amount'],
            'extend_arg'    =>  $params['extend_arg'],
        );
        $data['shop_type'] = $shops_detail['node_type'];
        $data['node_id']   = $shops_detail['node_id'];
        return $data;
    }

}

?>