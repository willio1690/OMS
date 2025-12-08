<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店发货单接口请求类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_request_delivery extends erpapi_store_request_abstract
{
    /**
     * 发货单暂停
     *
     * @return void
     * @author 
     **/

    public function delivery_pause($sdf){}

    /**
     * 发货单暂停恢复
     *
     * @return void
     * @author 
     **/
    public function delivery_renew($sdf){}

    /**
     * 发货单创建
     *
     * @return void
     * @author 
     **/
    public function delivery_create($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '发货单添加';

        $params = $this->_format_delivery_create_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        // 御城河
        /*
        $tradeIds = explode('|',$sdf['order_bn']);
        kernel::single('base_hchsafe')->order_send_log($tradeIds,$this->__channelObj->wms['node_id']);
        */

        $callback = array(
            'class' => get_class($this),
            'method' => 'delivery_create_callback',
            'params' => array('delivery_bn'=>$delivery_bn,'obj_bn'=>$delivery_bn,'obj_type'=>'delivery'),
        );

        return $this->__caller->call(WMS_SALEORDER_CREATE, $params, $callback, $title,10,$delivery_bn);
    }

    protected function _format_delivery_create_params($sdf)
    {
        $delivery_bn = $sdf['outer_delivery_bn'];

        $delivery_items = $sdf['delivery_items'];
        $sdf['item_total_num'] = $sdf['line_total_count'] = count($delivery_items);

        $items = array();
        if ($delivery_items){
            sort($delivery_items);
            foreach ($delivery_items as $k => $v){
                $items[] = array(
                    'bn'       => $v['bn'],
                    'name'       => $v['product_name'],
                    'number'   => (int)$v['number'],
                    //'sku_type'  =>  'product',
                    'sale_price'=>  $v['sale_price'],
                );
            }
        }

        
        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);

        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($this->__channelObj->wms['channel_id'],$sdf['logi_code']);
        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'],$sdf['shop_code']);
        $params = array(
           
            'shipping_id'         => $delivery_bn,
           // 'order_source'        => $sdf['shop_type'] ? strtoupper($sdf['shop_type']) : 'OTHER',
            'shipping_type'       => 'EXPRESS',
            'shipping_fee'        => $sdf['logistics_costs'],
            'tid'                 => $sdf['order_bn'],
            'logistics_code'      => $logistics_code ? $logistics_code : $sdf['logi_code'],
            'shop_code'           => $shop_code ? $shop_code : $sdf['shop_code'],
            'branch_code'         =>  $sdf['branch_bn'],  
            'buyer_id'            => '',
            'shipping_type'       => '',
            'shipping_fee'        => '',
            'logistics_company'   => '',
            'logistics_no'        => '',  
            'remark'              => $sdf['memo'],//订单上的客服备注
            'created'             => $create_time,
            'is_protect'          => $sdf['is_protect'],
            'protect_fee'         => $sdf['cost_protect'],
            'is_cod'              => $sdf['is_cod'],//是否货到付款。可选值:true(是),false(否)
        
            'receiver_name'       => $sdf['consignee']['name'],
            'receiver_zip'        => $sdf['consignee']['zip'],
            'receiver_phone'      => $sdf['consignee']['telephone'],
            'receiver_mobile'     => $sdf['consignee']['mobile'],
            'receiver_state'      => $sdf['consignee']['province'],
            'receiver_city'       => $sdf['consignee']['city'],
            'receiver_district'   => $sdf['consignee']['district'],
            'receiver_address'    => $sdf['consignee']['addr'],
            'receiver_email'      => $sdf['consignee']['email'],
            'receiver_time'       => $sdf['consignee']['r_time'],// TODO: 要求到货时间
            'status'              => 'READY', 
            'shipping_operator'   => '', 
            'shipping_items'      => $items,
          
        );
        return $params;
    }

    /**
     * delivery_create_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function delivery_create_callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $delivery_bn = $callback_params['delivery_bn'];

        if ($data) $data = @json_decode($data,true);

        if (is_array($data) && $data['wms_order_code']) {
            $oDelivery_extension = app::get('console')->model('delivery_extension');
            $ext_data['original_delivery_bn'] = $data['wms_order_code'];
            $ext_data['delivery_bn']          = $delivery_bn;
            $oDelivery_extension->create($ext_data);
        }

        $deliveryObj = app::get('ome')->model('delivery');
        $deliverys = $deliveryObj ->dump(array('delivery_bn'=>$delivery_bn),'delivery_id');
        
        $msg        = $err_msg ? $err_msg : $res;
        $api_status = $rsp=='succ' ? 'send_succ' : 'send_fail';
        kernel::single('console_delivery')->update_sync_status($deliverys['delivery_id'], $api_status, $msg);

        $callback_params['obj_bn'] = $delivery_bn;
        $callback_params['obj_type'] = 'delivery';
        return $this->callback($response, $callback_params);
    }


    /**
     * 发货单取消
     * 
     * @return void
     * @author 
     * */
    public function delivery_cancel($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '发货单取消';

        $params = $this->_format_delivery_cancel_params($sdf);

        return $this->__caller->call(WMS_SALEORDER_CANCEL, $params, null, $title,20,$delivery_bn);

    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params = array(
            'store_bn' => $sdf['branch_bn'],
            'out_order_code' => $sdf['outer_delivery_bn'],
        );
        return $params;
    }


    public function delivery_o2o_pickup($sdf){

        $order_bn = $sdf['order_bn'];
        $title = $this->__channelObj->wms['channel_name'] . '自提通知单';
        $params = $this->_format_delivery_o2o_pickup_params($sdf);

        return $this->__caller->call('store.wms.saleorder.o2o_pickup', $params, null, $title,20,$order_bn);
    }

        /**
     * _format_delivery_o2o_pickup_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function _format_delivery_o2o_pickup_params($sdf){

        return $sdf;
    }
    
}