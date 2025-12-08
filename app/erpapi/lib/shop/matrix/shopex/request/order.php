<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_request_order extends erpapi_shop_request_order 
{
    static public $order_status = array(
        'active' => 'TRADE_ACTIVE',
        'dead'   => 'TRADE_CLOSED',
        'finish' => 'TRADE_FINISHED',
    );
    static public $order_object_type = array(
        'goods' => 'goods',
        'gift'  => 'gift',
    );
    static public $order_pay_status = array(
        '0' => 'PAY_NO',
        '1' => 'PAY_FINISH',
        '2' => 'PAY_TO_MEDIUM',
        '3' => 'PAY_PART',
        '4' => 'REFUND_PART',
        '5' => 'REFUND_ALL',
    );
    static public $order_ship_status = array(
        '0' => 'SHIP_NO',
        '1' => 'SHIP_FINISH',
        '2' => 'SHIP_PART',
        '3' => 'RESHIP_PART',
        '4' => 'RESHIP_ALL',
    );

    /**
     * 更新Order
     * @param mixed $order order
     * @return mixed 返回值
     */

    public function updateOrder($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        //店铺信息
        $shop_info = $this->__channelObj->channel;
        // 订单明细
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump(array('order_id' => $order['order_id']), '*', array('order_objects'=>array('*',array('order_items'=>array('*')))));
        if($order['source'] == 'local') {
            $rs['msg'] = '本地新建订单不同步到前台';
            return $rs;
        }
        $materialextObj = kernel::single('material_basic_material');
        $specModel    = app::get('ome')->model('specification');
        $gzip = false;
        $max_orderitems = ome_order_func::get_max_orderitems();
        $object_key = $order_items_num = 0;
        if ($order['order_objects']){
            foreach ($order['order_objects'] as $objects){
                $order_objects['order'][$object_key] = array(
                    'oid'             => $objects['shop_goods_id'],
                    'type'            => self::$order_object_type[$objects['obj_type']] ? self::$order_object_type[$objects['obj_type']] : 'goods',
                    'type_alias'      => $objects['obj_alias'],
                    'iid'             => $objects['shop_goods_id'],
                    'title'           => $objects['name'],
                    'orders_bn'       => $objects['bn'],
                    'items_num'       => $objects['quantity'],
                    'total_order_fee' => $objects['amount'],
                    'weight'          => $objects['weight'],
                    'discount_fee'    => $objects['pmt_price'],
                    'sale_price'      => $objects['sale_price'],
                );
                if ($objects['order_items']){
                    foreach ($objects['order_items'] as $items){
                        $product_id = $items['product_id'];
                       
                        $materialext = $materialextObj->getBasicMaterialBybn($items['bn']);

                        $product_attr = $materialext['specifications'];
                        $order_objects['order'][$object_key]['order_items']['item'][] = array(
                            'sku_id'         => $product_id,
                            'bn'             => $items['bn'],
                            'name'           => $items['name'],
                            'sku_properties' => $product_attr,
                            'weight'         => $items['weight'],
                            'score'          => $items['score'],
                            'price'          => $items['price'],
                            'total_item_fee' => $items['amount'],
                            'discount_fee'   => $items['pmt_price'],
                            'sale_price'     => $items['sale_price'],
                            'num'            => $items['quantity'],
                            'sendnum'        => $items['sendnum'],
                            'item_type'      => $items['item_type'] ? $items['item_type']:'product',
                            'item_status'    => $items['delete'] == 'false' ? 'normal' : 'cancel',
                        );
                    }
                }
                $order_items_num += count($objects['order_items']);
                $object_key++;
            }
        }
        $pmtModel = app::get('ome')->model('order_pmt');
        //优惠方案
        $pmt_detail = $pmtModel->getList('pmt_amount as promotion_fee,pmt_describe as promotion_name', array('order_id'=>$order['order_id']));
        $regionLib = kernel::single('ome_func');
        //收货人地区信息
        $area = $order['consignee']['area'];
        $regionLib->split_area($area);
        //买家会员信息
        $memberModel = app::get('ome')->model('members');
        $members_info = $memberModel->dump(array('member_id'=>$order['member_id']), '*');
        $member_area = $members_info['contact']['area'];
        $regionLib->split_area($member_area);
        
        //params
        $params = array(
            'tid'                    => $order['order_bn'],
            'created'                => date('Y-m-d H:i:s',$order['createtime']),
            'modified'               => date('Y-m-d H:i:s',$order['last_modified']),
            'status'                 => self::$order_status[$order['status']],
            'pay_status'             => self::$order_pay_status[$order['pay_status']],
            'ship_status'            => self::$order_ship_status[$order['ship_status']],
            'is_delivery'            => $order['is_delivery']=='Y' ? 'true' : 'false',
            'is_cod'                 => $order['shipping']['is_cod'],
            'has_invoice'            => $order['is_tax'],
            'invoice_title'          => $order['tax_title'],
            'invoice_fee'            => $order['cost_tax'],
            'total_goods_fee'        => $order['cost_item'],
            'total_trade_fee'        => $order['total_amount'],
            'total_currency_fee'     => $order['total_amount'],
            'discount_fee'           => $order['discount'],
            'goods_discount_fee'     => $order['pmt_goods'],
            'orders_discount_fee'    => $order['pmt_order'],
            'promotion_details'      => $pmt_detail ? json_encode($pmt_detail) : '',
            'payed_fee'              => $order['payed'],
            'currency'               => $order['currency'] ? $order['currency'] : 'CNY',
            'currency_rate'          => $order['cur_rate'],
            'pay_cost'               => $order['payinfo']['cost_payment'],
            'buyer_obtain_point_fee' => $order['score_g'],
            'point_fee'              => $order['score_u'],
            'shipping_type'          => $order['shipping']['shipping_name'],
            'shipping_fee'           => $order['shipping']['cost_shipping'],
            'is_protect'             => $order['shipping']['is_protect'],
            'protect_fee'            => $order['shipping']['cost_protect'],
            'payment_type'           => $order['payinfo']['pay_name'],
            'receiver_name'          => $order['consignee']['name'],
            'receiver_email'         => $order['consignee']['email'],
            'receiver_state'         => $area[0],
            'receiver_city'          => $area[1],
            'receiver_district'      => $area[2],
            'receiver_address'       => $order['consignee']['addr'],
            'receiver_zip'           => $order['consignee']['zip'],
            'receiver_mobile'        => $order['consignee']['mobile'],
            'receiver_phone'         => $order['consignee']['telephone'],
            'receiver_time'          => $order['consignee']['r_time'],
            'buyer_uname'            => $members_info['account']['uname'],
            'buyer_name'             => $members_info['contact']['name'],
            'buyer_mobile'           => $members_info['contact']['phone']['mobile'],
            'buyer_phone'            => $members_info['contact']['phone']['telephone'],
            'buyer_email'            => $members_info['contact']['email'],
            'buyer_state'            => $member_area[0],
            'buyer_city'             => $member_area[1],
            'buyer_district'         => $member_area[2],
            'buyer_address'          => $members_info['contact']['addr'],
            'buyer_zip'              => $members_info['contact']['zipcode'],
            'seller_mobile'          => $shop_info['mobile'],
            'seller_phone'           => $shop_info['tel'],
            'seller_name'            => $shop_info['default_sender'],
            'total_weight'           => $order['weight'],
            'orders'                 => $order_objects ? json_encode($order_objects) : '',
        );
        $title = '店铺('.$shop_info['name'].')订单编辑(订单号:'.$order['order_bn'].')';

        $callback = array();

        if ($shop_info['node_type'] != 'ecshop_b2c')
            $callback = array(
                'class' => get_class($this),
                'method' => 'callback',
            );

        if ($order_items_num > $max_orderitems){
            $gzip = true;
        }
        //$params['gzip'] = $gzip;TODO:暂时不走GZIP
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    protected function _formatUpdateOrderStatusParam($order, $status, $memo, $mode) {
        $order_status = $status ? $status : $order['status'];
        $params = array();
        $params['tid']                    = $order['order_bn'];
        $params['status']                 = self::$order_status[$order_status];
        $params['type']                   = 'status';
        $params['modify']                 = date('Y-m-d H:i:s', time());
        $params['is_update_trade_status'] = 'true';
        if ($order_status == 'dead'){
            //订单取消理由
            $params['reason'] = $memo;
        }
        return $params;
    }

    /**
     * 更新OrderStatus
     * @param mixed $order order
     * @param mixed $status status
     * @param mixed $memo memo
     * @param mixed $mode mode
     * @return mixed 返回值
     */
    public function updateOrderStatus($order , $status='' , $memo='' , $mode='sync')
    {
        $rs = array('rsp'=>'fail','msg'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $shop    = $this->__channelObj->channel;
        $params = $this->_formatUpdateOrderStatusParam($order, $status, $memo, $mode);
        if(empty($params['status'])) {
            $rs['msg'] = '状态不回写';
            return $rs;
        }
        $title = '店铺('.$shop['name'].')更新[订单状态]:'.$params['status'].'(订单号:'.$order['order_bn'].')';
        $callback = array();
        if($mode != 'sync') {
            $callback = array(
                'class' => get_class($this),
                'method' => 'callback',
            );
        }
        $result = $this->__caller->call(SHOP_UPDATE_TRADE_STATUS_RPC, $params, $callback, $title, 10, $order['order_bn']);
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'] ? json_decode($result['data'], true) : array();
        return $rs;
    }

    /**
     * 更新OrderTax
     * @param mixed $order order
     * @return mixed 返回值
     */
    public function updateOrderTax($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid']    = $order['order_bn'];
        $params['tax_no'] = $order['tax_no'];
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[订单发票号]:'.$order['tax_no'].'(订单号:'.$order['order_bn'].')';
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_TAX_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 更新OrderShipStatus
     * @param mixed $order order
     * @param mixed $queue queue
     * @return mixed 返回值
     */
    public function updateOrderShipStatus($order,$queue = false)
    {
        $rs = array('rsp' => 'fail', 'msg' => '', 'data' => '');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $_in_mq = $this->__caller->caller_into_mq('sms_sendOne','sms',$order['ship_id'],array($order),$queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }
        $params['tid'] = $order['order_bn'];
        $params['ship_status'] = self::$order_ship_status[$order['ship_status']];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺(' . $this->__channelObj->channel['name'] . ')更新[订单发货状态]:' . $params['ship_status'] . '(订单号:' . $order['order_bn'] . ')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_SHIP_STATUS_RPC, $params, $callback, $title, 10,$order['order_bn']);
        return $rs;
    }

    /**
     * 更新OrderPayStatus
     * @param mixed $order order
     * @return mixed 返回值
     */
    public function updateOrderPayStatus($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid'] = $order['order_bn'];
        $params['pay_status'] = self::$order_pay_status[$order['pay_status']];
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[订单支付状态]:'.$params['pay_status'].'(订单号:'.$order['order_bn'].')';
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_PAY_STATUS_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    static public $order_mark_type = array(
        'b0' => '0',
        'b1' => '1',
        'b2' => '2',
        'b3' => '3',
        'b4' => '4',
        'b5' => '5',
    );
    /**
     * 更新OrderMemo
     * @param mixed $order order
     * @param mixed $memo memo
     * @return mixed 返回值
     */
    public function updateOrderMemo($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid']      = $order['order_bn'];
        $params['memo']     = $memo['op_content'];
        $params['flag']     = self::$order_mark_type[$order['mark_type']] ? self::$order_mark_type[$order['mark_type']] : '';
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新订单备注(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_MEMO_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 添加OrderMemo
     * @param mixed $order order
     * @param mixed $memo memo
     * @return mixed 返回值
     */
    public function addOrderMemo($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid']      = $order['order_bn'];
        $params['memo']     = $memo['op_content'];
        $params['flag']     = self::$order_mark_type[$order['mark_type']] ? self::$order_mark_type[$order['mark_type']] : '';
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')添加订单备注(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_ADD_TRADE_MEMO_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 添加OrderCustomMark
     * @param mixed $order order
     * @param mixed $memo memo
     * @return mixed 返回值
     */
    public function addOrderCustomMark($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid']      = $order['order_bn'];
        $params['message']  = $memo['op_content'];
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')添加客户备注(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_ADD_TRADE_BUYER_MESSAGE_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    protected function __formatUpdateOrderShippingInfo($order){
        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
        }
        $params = array();
        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_state']    = $receiver_state ? $receiver_state : '';
        $params['receiver_city']     = $receiver_city ? $receiver_city : '';
        $params['receiver_district'] = $receiver_district ? $receiver_district : '';
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        $params['receiver_email']    = $order['consignee']['email']?$order['consignee']['email']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_time']     = $order['consignee']['r_time']?$order['consignee']['r_time']:'';
        return $params;
    }

    /**
     * 更新OrderConsignerinfo
     * @param mixed $order order
     * @return mixed 返回值
     */
    public function updateOrderConsignerinfo($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $consigner_area = $order['consigner']['area'];
        kernel::single('ome_func')->split_area($consigner_area);
        $params['tid']              = $order['order_bn'];
        $params['shipper_name']     = $order['consigner']['name'];
        $params['shipper_state']    = $consigner_area[0];
        $params['shipper_city']     = $consigner_area[1];
        $params['shipper_district'] = $consigner_area[2];
        $params['shipper_address']  = $order['consigner']['addr'];
        $params['shipper_zip']      = $order['consigner']['zip'];
        $params['shipper_email']    = $order['consigner']['email'];
        $params['shipper_mobile']   = $order['consigner']['mobile'];
        $params['shipper_phone']    = $order['consigner']['tel'];
        $callback = array(
            'class'  => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易发货人信息]:'.$params['consigner_name'].'(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_SHIPPER_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 更新OrderSellagentinfo
     * @param mixed $order order
     * @return mixed 返回值
     */
    public function updateOrderSellagentinfo($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $sellagentObj = app::get('ome')->model('order_selling_agent');
        $sellagent_detail = $sellagentObj->dump(array('order_id' => $order['order_id']), '*');
        $sellagent_area = $sellagent_detail['member_info']['area'];
        kernel::single('ome_func')->split_area($sellagent_area);
        $params = array(
            'tid'             => $order['order_bn'],
            '_uname'          => $sellagent_detail['member_info']['uname'],
            '_name'           => $sellagent_detail['member_info']['name'],
            '_birthday'       => $sellagent_detail['member_info']['birthday'],
            '_sex'            => $sellagent_detail['member_info']['sex'],
            '_state'          => $sellagent_area[0],
            '_city'           => $sellagent_area[1],
            '_district'       => $sellagent_area[2],
            '_address'        => $sellagent_detail['member_info']['addr'],
            '_zip'            => $sellagent_detail['member_info']['zip'],
            '_email'          => $sellagent_detail['member_info']['email'],
            '_mobile'         => $sellagent_detail['member_info']['mobile'],
            '_phone'          => $sellagent_detail['member_info']['tel'],
            '_website_name'   => $sellagent_detail['website']['name'],
            '_website_domain' => $sellagent_detail['website']['domain'],
        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易代销人信息]:(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_SELLING_AGENT_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 更新OrderLimitTime
     * @param mixed $order order
     * @param mixed $order_limit_time order_limit_time
     * @return mixed 返回值
     */
    public function updateOrderLimitTime($order,$order_limit_time)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params = array();
        $params['tid']              = $order['order_bn'];
        $params['order_limit_time'] = $order_limit_time;
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '更新店铺('.$this->__channelObj->channel['name'].')订单失效时间(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_ORDER_LIMITTIME_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * cleanStockFreeze
     * @param mixed $order order
     * @return mixed 返回值
     */
    public function cleanStockFreeze($order)
    {
        $params = array();
        $params['tid'] = $order['order_bn'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')清除预占库存(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_ITEM_FREEZSTORE_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }
}