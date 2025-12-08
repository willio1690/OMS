<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_request_delivery extends erpapi_shop_request_delivery
{
    //发货状态对应关系
    static public $ship_status = array(
        'succ'     =>'SUCC',
        'failed'   =>'FAILED',
        'cancel'   =>'CANCEL',
        'lost'     =>'LOST',
        'progress' =>'PROGRESS',
        'timeout'  =>'TIMEOUT',
        'ready'    =>'READY',
        'stop'     =>'STOP',
        'back'     =>'BACK',
        'verify'   =>'VERIFY',//TODO:新增加的校验
    );

    /**
     * 添加发货单
     *
     * @return void
     * @author 
     **/

    public function add($sdf)
    {
        if($sdf['type'] == 'reject') return $this->succ('原样寄回，不向平台发送请求');

        $params = $this->get_add_params($sdf);

        $title = sprintf('添加发货单[%s]',$sdf['delivery_bn']);

        $callback = array(
           'class'  => get_class($this),
           'method' => 'add_callback',
           'params' => array(
                'logi_no' => $sdf['logi_no'], // 运单号
                'obj_bn' => $sdf['orderinfo']['order_bn'],
            ),
        );

        return $this->__caller->call(SHOP_TRADE_SHIPPING_ADD, $params, $callback, $title,10,$sdf['orderinfo']['order_bn']);
    }

    /**
     * 添加发货单回调
     *
     * @return void
     * @author 
     **/
    public function add_callback($response, $callback_params)
    {
        $logi_no = $callback_params['logi_no'];
        $rsp     = $response['rsp'];

        // 如果拆单，更新状态
        if ($logi_no && $this->getDeliverySeting()) {
            $dlysyncModel = app::get('ome')->model('delivery_sync');
            $dlysyncModel->update(array('sync'=>$rsp, 'dateline'=>time()), array('logi_no'=>$logi_no));
        }

        return $this->callback($response, $callback_params);
    }

    /**
     * 添加发货单参数
     *
     * @return void
     * @author 
     **/
    protected function get_add_params($sdf)
    {

        // 发货明细
        $delivery_items = array();
        #防止重复,捆绑商品有多个delivery_items
        $repeat_order_objects    = array();
        $order_id = $sdf['orderinfo']['order_id'];
        //拆单的不管回写第一单还是最后单，只回写发货单上的内容和数量
        $deliItemModel = app::get('ome')->model('delivery_items');
        $deliItemDModel = app::get('ome')->model('delivery_items_detail');
        $orderItemModel = app::get('ome')->model('order_items');
        $delivery_item_list = $deliItemModel->getList('item_id as delivery_item_id,delivery_id,number',array('delivery_id'=>$sdf['delivery_id']));

        foreach($delivery_item_list as $key=>$item){
            $deliItemDInfo = $deliItemDModel->getList('*',array('delivery_item_id'=>$item['delivery_item_id'], 'delivery_id'=>$item['delivery_id'], 'order_id'=>$order_id), 0, 1);
            //不是捆绑的obj和item数量是相同的，名称货号取obj 数量以发货单明细发货数量为主
            if($deliItemDInfo[0]['item_type'] != 'pkg'){
                foreach ($sdf['orderinfo']['order_objects'] as $key=>$obj) {
                    if(($key == $deliItemDInfo[0]['order_obj_id']) && $obj['shop_goods_id'] && $obj['shop_goods_id'] > 0){
                        $delivery_items[] = array(
                            'number' => $item['number'],
                            'name' => trim($obj['name']),
                            'bn' => trim($obj['bn']),
                        );
                    }
                }

            }else{
                //如果是捆绑的，根据原来obj item对应比例乘以已发的算出obj层已发的数量
                $orderItemInfo = $orderItemModel->getList('*',array('obj_id'=>$deliItemDInfo[0]['order_obj_id'], 'item_id'=>$deliItemDInfo[0]['order_item_id'], 'order_id'=>$order_id), 0, 1);
                foreach ($sdf['orderinfo']['order_objects'] as $key=>$obj) {
                    if(($key == $deliItemDInfo[0]['order_obj_id']) && $obj['shop_goods_id'] && $obj['shop_goods_id'] > 0)
                    {
                        #防止重复,捆绑商品有多个delivery_items
                        if($repeat_order_objects[$key])
                        {
                            continue;
                        }
                        $repeat_order_objects[$key]    = $obj;

                        $delivery_items[] = array(
                            'number' => $obj['quantity']/$orderItemInfo[0]['nums']*$item['number'],
                            'name' => trim($obj['name']),
                            'bn' => trim($obj['bn']),
                        );
                    }
                }
            }
        }


        // 发货地址
        $consignee_area = $this->__channelObj->channel['area'];
        kernel::single('ome_func')->split_area($consignee_area);
        $receiver_state    = ome_func::strip_bom(trim($consignee_area[0]));
        $receiver_city     = ome_func::strip_bom(trim($consignee_area[1]));
        $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

        $sdf['receiver']['receiver_state']    = $receiver_state;
        $sdf['receiver']['receiver_city']     = $receiver_city;
        $sdf['receiver']['receiver_district'] = $receiver_district;

        $param = array(
            'tid'               => $sdf['orderinfo']['order_bn'],
            'shop_id'           => $this->__channelObj->channel['shop_id'],
            'shipping_fee'      => $sdf['delivery_cost_actual'] ? $sdf['delivery_cost_actual'] :'',  // 实际物流费
            'shipping_id'       => $sdf['delivery_bn'],
            'create_time'       => date('Y-m-d H:i:s',$sdf['create_time']),
            'is_protect'        => $sdf['is_protect'],                                               // 是否保价
            'is_cod'            => $sdf['is_cod'],
            'buyer_id'          => $sdf['memberinfo']['uname'],                                      // 会员帐号
            'status'            => self::$ship_status[$sdf['status']],                               // 发货状态
            'shipping_type'     => $sdf['delivery']  ? $sdf['delivery']     : '',                    // 配送方式
            'logistics_id'      => $sdf['logi_id']   ? $sdf['logi_id']      : '',                    // 物流公司ID
            'logistics_company' => $sdf['logi_name'] ? $sdf['logi_name']    : '',                    // 物流公司
            'logistics_no'      => $sdf['logi_no']   ? $sdf['logi_no']      : '',                    // 运单号
            'logistics_code'    => $sdf['logi_type'],                                                // 物流公司编码
            'receiver_name'     => $sdf['consignee']['name']                ? $sdf['consignee']['name'] : '',
            // ？取的是发货地区，有待验证
            'receiver_state'    => $sdf['receiver']['receiver_state']       ? $sdf['receiver']['receiver_state']    : '',
            'receiver_city'     => $sdf['receiver']['receiver_city']        ? $sdf['receiver']['receiver_city']     : '',
            'receiver_district' => $sdf['receiver']['receiver_district']    ? $sdf['receiver']['receiver_district'] : '',

            'receiver_address'  => $sdf['consignee']['addr']                ? $sdf['consignee']['addr'] :'',
            'receiver_zip'      => $sdf['consignee']['zip']                 ? $sdf['consignee']['zip']:'',
            'receiver_email'    => $sdf['consignee']['email']               ? $sdf['consignee']['email']:'',
            'receiver_mobile'   => $sdf['consignee']['mobile']              ? $sdf['consignee']['mobile']:'',
            'receiver_phone'    => $sdf['consignee']['telephone']           ? $sdf['consignee']['telephone']:'',
            'memo'              => $sdf['memo']                             ? $sdf['memo']:'',
            't_begin'           => date('Y-m-d H:i:s',$sdf['create_time']),
            'refund_operator'   => kernel::single('desktop_user')->get_login_name(),
            'shipping_items'    => json_encode($delivery_items),
            'ship_type'         => 'delivery',
            'modify'            => date('Y-m-d H:i:s',$sdf['last_modified']),
        );
        
        return $param;
    }

    /**
     * 更新发货单流水状态
     *
     * @return void
     * @author 
     **/
    public function deliveryprocess_update($sdf)
    {
        if ($sdf['status'] == 'succ') return $this->succ('更新发货状态不走此接口');

        // 获取请示参数
        $params = $this->get_deliveryprocess_update_params($sdf);

        // 回调
        $callback = array(
           'class'  => get_class($this),
           'method' => 'callback',
            'params' => array(
                'obj_bn' => $sdf['orderinfo']['order_bn'],
            ),
        );

        // 标题
        $title = sprintf('更新发货单流水状态[%s]',$sdf['delivery_bn']);

        return $this->__caller->call(SHOP_TRADE_SHIPPING_STATUS_UPDATE, $params, $callback, $title,10,$sdf['orderinfo']['order_bn']);
    }

    /**
     * 发货单流水状态参数
     *
     * @return void
     * @author 
     **/    
    protected function get_deliveryprocess_update_params($sdf)
    {
        $param = array(
            'tid'         => $sdf['orderinfo']['order_bn'],
            'shipping_id' => $sdf['delivery_bn'],
            'status'      => self::$ship_status[$sdf['status']],
        );

        return $param;
    }


    /**
     * 更新物流公司
     *
     * @return void
     * @author 
     **/
    public function logistics_update($sdf)
    {
        // 获取请示参数
        $params = $this->get_logistics_update_params($sdf);

        // 回调
        $callback = array(
           'class'  => get_class($this),
           'method' => 'callback',
            'params' => array(
                'obj_bn' => $sdf['delivery_bn'],
            ),
        );

        // 标题
        $title = sprintf('更新物流信息[%s]',$sdf['orderinfo']['order_bn']);

        return $this->__caller->call(SHOP_TRADE_SHIPPING_UPDATE, $params, $callback, $title,10,$sdf['delivery_bn']);
    }

    /**
     * 获取物流公司参娄
     *
     * @return void
     * @author 
     **/
    public function get_logistics_update_params($sdf)
    {
        $param['tid']               = $sdf['orderinfo']['order_bn'];
        $param['shipping_id']       = $sdf['delivery_bn'];
        $param['logistics_code']    = $sdf['logi_type']    ? $sdf['logi_type'] : '';
        $param['logistics_company'] = $sdf['logi_name']    ? $sdf['logi_name'] : '';
        $param['logistics_no']      = $sdf['logi_no']      ? $sdf['logi_no']   : '';

        return $param;
    }


    protected function get_delivery_apiname($sdf)
    {
        return SHOP_TRADE_SHIPPING_STATUS_UPDATE;
    }

    protected function get_confirm_params($sdf)
    {
        $param = array(
            'tid'         => $sdf['orderinfo']['order_bn'],
            'shipping_id' => $sdf['delivery_bn'],
            'status'      => self::$ship_status[$sdf['status']],
        );

        return $param;
    }
}