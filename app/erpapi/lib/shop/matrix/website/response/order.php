<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_website_response_order extends erpapi_shop_response_order
{
    var $status = array('TRADE_ACTIVE' => 'active', 'TRADE_CLOSED' => 'dead', 'TRADE_FINISHED' => 'finish');
    var $pay_status = array('PAY_NO'        => 0,
                            'PAY_FINISH'    => 1,
                            'PAY_TO_MEDIUM' => 2,
                            'PAY_PART'      => 3,
                            'REFUND_PART'   => 4,
                            'REFUND_ALL'    => 5,
                            'REFUNDING'     => 6
    );
    var $ship_status = array('SHIP_NO'      => 0,
                             'SHIP_FINISH'  => 1,
                             'SHIP_PREPARE' => 1,
                             'SHIP_PART'    => 2,
                             'RESHIP_PART'  => 3,
                             'RESHIP_ALL'   => 4
    );

    var $item_status = array(
        'TRADE_ACTIVE' => 'active',
        'TRADE_CLOSED'  => 'close',
        'TRADE_FINISHED' => 'close',
    );

    // 校验排除项
    protected $_checkExcludeList = [
        'item_movement_code', // 赠品明细movement_code
        //'pmt_discount_code' // discount_code
    ];

    protected function _analysis()
    {

        $this->formatData();

        $this->__apilog['result']['data'] = array('tid' => $this->_ordersdf['order_bn']);
        $this->__apilog['original_bn'] = $this->_ordersdf['order_bn'];

        parent::_analysis();


        if(in_array($this->_ordersdf['trade_type'],array('step')) || in_array($this->_ordersdf['t_type'],array('step'))){
            $this->_ordersdf['order_type'] = 'presale';
        }
        foreach($this->_ordersdf['order_objects'] as $object){
            if($object['zhengji_status']){
                if(in_array($object['zhengji_status'],array('1','2'))){
                    $this->_ordersdf['order_type'] = 'presale';

                }
            }
        }

    }

    function formatData(){
        $aData = $this->_ordersdf;
        unset($this->_ordersdf);

        $order_sdf['order_bn'] = $aData['tid'];
        $order_sdf['status'] = $this->status[$aData['status']];
        $order_sdf['source_status'] = $aData['status'];
        $order_sdf['pay_status'] = $this->pay_status[$aData['pay_status']];
        $order_sdf['ship_status'] = $this->ship_status[$aData['ship_status']];
        $order_sdf['is_delivery'] = $aData['is_delivery'];

        //配送信息 begin
        $shipping['shipping_id'] = $aData['shipping_tid'];
        $shipping['shipping_name'] = $aData['shipping_type'];
        $shipping['cost_shipping'] = $aData['shipping_fee'];
        $shipping['is_protect'] = $aData['is_protect'];
        $shipping['cost_protect'] = $aData['protect_fee'];
        $shipping['is_cod'] = $aData['is_cod'];
        //配送信息 end
        $order_sdf['shipping'] = $shipping;
        //支付方式信息 begin
        $payinfo['pay_name'] = $aData['payment_type'];
        $payinfo['cost_payment'] = $aData['pay_cost'];  //支付费用

        //支付方式信息 end
        $order_sdf['payinfo'] = $payinfo;
        $order_sdf['is_sh_ship'] = $aData['is_sh_ship'] ? $aData['is_sh_ship'] : '';#菜鸟自动流转订单
        $order_sdf['pay_bn'] = $aData['payment_tid'];
        $order_sdf['weight'] = $aData['total_weight'];
        $order_sdf['title'] = $aData['title'];
        $order_sdf['createtime'] = $aData['created'];
        // 收货人信息 begin
        $consignee['name'] = $aData['receiver_name'];
        $consignee['area_state'] = $aData['receiver_state'];
        $consignee['area_city'] = $aData['receiver_city'];
        $consignee['area_district'] = $aData['receiver_district'];
        $consignee['addr'] = $aData['receiver_address'];
        $consignee['zip'] = $aData['receiver_zip'];
        $consignee['telephone'] = $aData['receiver_phone'];
        $consignee['mobile'] = $aData['receiver_mobile'];
        $consignee['email'] = $aData['receiver_email'];
        $consignee['r_time'] = $aData['receiver_time'];
        //收货人信息 end
        $order_sdf['consignee'] = $consignee;
        //发货人信息 begin    暂时没有找到 用发货人信息代替
        $consigner['name'] = $aData['receiver_name'];
        $consigner['area_state'] = $aData['receiver_state'];
        $consigner['area_city'] = $aData['receiver_city'];
        $consigner['area_district'] = $aData['receiver_district'];
        $consigner['addr'] = $aData['receiver_address'];
        $consigner['zip'] = $aData['receiver_zip'];
        $consigner['telephone'] = $aData['receiver_phone'];
        $consigner['mobile'] = $aData['receiver_mobile'];
        $consigner['email'] = $aData['receiver_email'];
        //发货人信息 end
        $order_sdf['consigner'] = $consigner;

        //买家会员信息 begin
        $member_info['uname'] = $aData['buyer_uname'];
        $member_info['name'] = $aData['buyer_name'];
        $member_info['alipay_no'] = $aData['buyer_alipay_no'];
        $member_info['area_state'] = $aData['buyer_state'];
        $member_info['area_city'] = $aData['buyer_city'];
        $member_info['area_district'] = $aData['buyer_district'];
        $member_info['addr'] = $aData['buyer_address'];
        $member_info['mobile'] = $aData['buyer_mobile'];
        $member_info['tel'] = $aData['buyer_phone'];
        $member_info['email'] = $aData['buyer_email'];
        $member_info['zip'] = $aData['buyer_zip'];

        //买家会员信息 end
        $order_sdf['member_info'] = json_encode($member_info);
        //订单来源
        $order_sdf['order_source'] = $aData['order_source'];

        if ($aData['order_source'] == 'app') {
            $order_sdf['order_source'] = 'I3';
        } else {
            $order_sdf['order_source'] = 'I';
        }

        //订单优惠方案信息  begin
        $tmp_pmt_detail = json_decode($aData['promotion_details'], true);

        $order_sdf['pmt_detail'] = array();
        $order_sdf['other_list'] = array();
        $k_count = 0;
        if ($tmp_pmt_detail) {
            foreach ((array)$tmp_pmt_detail as $k => $v) {
                $order_sdf['pmt_detail'][$k]['pmt_amount'] = $v['promotion_fee'] ? $v['promotion_fee'] : $v['pmt_amount'];
                $order_sdf['pmt_detail'][$k]['pmt_describe'] = $v['promotion_name'] ? $v['promotion_name'] : $v['pmt_describe'];
                $order_sdf['pmt_detail'][$k]['oid'] = $v['oid'];
                $order_sdf['pmt_detail'][$k]['promotion_id'] = $v['promotion_id'];
                $order_sdf['pmt_detail'][$k]['discount_code'] = isset($v['discount_code']) ? $v['discount_code'] :'';
            }
        }

        $order_sdf['other_list'] = json_encode($aData['other_list']);

        $order_sdf['t_type'] = empty($aData['tradetype']) ? 'fixed' : $aData['tradetype'];
        $order_sdf['is_yushou'] = $aData['is_yushou'];  //全款预售标识 可选值：true（是），false（否）
        $order_sdf['trade_type'] = $aData['trade_type'];  //定金预售标识。可选值：step（是）
        $order_sdf['step_trade_status'] = $aData['step_trade_status'];  //分阶段付款状态
        $order_sdf['step_paid_fee'] = $aData['step_paid_fee'];  //定金金额

        $trade_memo = $aData['trade_memo'];

        if($trade_memo=='内购订单'){
            $order_sdf['order_type'] = 'staff';
        }
        //订单优惠方案信息  end
        //支付单信息  新版本
        $aData['payment_lists'] = json_decode($aData['payment_lists'], true);

        foreach ((array)$aData['payment_lists'] as $p_k => $p_v) {
            $payments[$p_k]['trade_no'] = $p_v['payment_id'];
            $payments[$p_k]['money'] = isset($p_v['currency_fee']) ? $p_v['currency_fee'] : $p_v['pay_fee'];
            $payments[$p_k]['pay_time'] = $p_v['pay_time'];
            $payments[$p_k]['account'] = $p_v['seller_account'];
            $payments[$p_k]['bank'] = $p_v['seller_bank'];
            $payments[$p_k]['pay_bn'] = $p_v['payment_id'];
            $payments[$p_k]['paycost'] = $p_v['paycost'];
            $payments[$p_k]['pay_account'] = $p_v['buyer_account'];
            $payments[$p_k]['paymethod'] = $p_v['payment_name'];
            $payments[$p_k]['memo'] = $p_v['memo'];
            $payments[$p_k]['outer_no'] = $p_v['outer_no'];  //支付网关的内部交易单号
        }

        $order_sdf['payments'] = $payments;
        //支付单信息  新版本
        $order_sdf['cost_item'] = $aData['total_goods_fee'];
        $order_sdf['currency'] = $aData['currency'];
        $order_sdf['cur_rate'] = $aData['currency_rate'];
        $order_sdf['score_u'] = $aData['point_fee'];
        $order_sdf['score_g'] = $aData['buyer_obtain_point_fee'];
        $order_sdf['discount'] = $aData['discount_fee'];
        $order_sdf['pmt_goods'] = $aData['goods_discount_fee'];
        $order_sdf['pmt_order'] = $aData['orders_discount_fee'];
        $order_sdf['total_amount'] = $aData['total_trade_fee']; //订单总额  = 交易应付总额
        $order_sdf['payed'] = $aData['payed_fee'];
        $order_sdf['custom_mark'] = $aData['buyer_message'] ? $aData['buyer_message'] : $aData['buyer_memo'];
        $order_sdf['mark_text'] = $aData['trade_memo'];
        $order_sdf['buyer_flag'] = $aData['buyer_flag'];
        $order_sdf['mark_type'] = $aData['seller_flag'];
        $order_sdf['order_limit_time'] = $aData['pay_time'];  //订单失效时间
        $order_sdf['coupons_name'] = $aData['coupons_name'] ? $aData['coupons_name']:''; //优惠卷名称

        $order_sdf['is_service_order'] = $aData['is_service_order'];
        $order_sdf['service_order_objects'] = $aData['service_orders'];

        //发票信息
        $order_sdf['is_tax'] = $aData['has_invoice'];
        $order_sdf['cost_tax'] = $aData['invoice_fee'];
        $order_sdf['invoice_amount'] = $aData['invoice_amount'];
        $order_sdf['tax_no'] = '';
        $order_sdf['tax_title'] = $aData['invoice_title'];
        $order_sdf['payer_register_no'] = $aData['payer_register_no'];
        $order_sdf['invoice_kind'] = ''; //1：电子发票  2：纸质发票      //todo 缺少字段
        $order_sdf['invoice_bank_name'] = $aData['invoice_bank_name'];
        $order_sdf['invoice_phone'] = $aData['invoice_buyer_phone'];
        $order_sdf['invoice_address'] = $aData['invoice_buyer_address'];
        $order_sdf['invoice_bank_account'] = $aData['invoice_bank_account'];

        //交易完成买家确认收货时间
        $order_sdf['end_time'] = $aData['end_time'];

        //订单商品结构数组信息
        $order_objects = array();
        $aData['orders'] = json_decode($aData['orders'],true);

        foreach ($aData['orders'] as $o_k => $o_v) {
            $order_objects[$o_k]['is_sh_ship'] = isset($o_v['is_sh_ship']) ? $o_v['is_sh_ship'] : '';
            $order_objects[$o_k]['obj_type'] = $o_v['type'];
            $order_objects[$o_k]['shop_goods_id'] = $o_v['iid'] ? $o_v['iid'] : '0';
            $order_objects[$o_k]['oid'] = $o_v['oid'];
            $order_objects[$o_k]['obj_alias'] = $o_v['type_alias'];
            $order_objects[$o_k]['name'] = $o_v['title']; //子订单名称
            $order_objects[$o_k]['price'] = $o_v['price']; //原始单价
            $order_objects[$o_k]['amount'] = $o_v['total_order_fee']; //原始价小计
            $order_objects[$o_k]['sale_price'] = $o_v['sale_price'];
            $order_objects[$o_k]['quantity'] = $o_v['items_num'];
            $order_objects[$o_k]['weight'] = $o_v['weight'];
            $order_objects[$o_k]['score'] = 0;//积分
            $order_objects[$o_k]['status'] = $o_v['status'] == 'TRADE_CLOSED' ? 'close' : 'active';
            $order_objects[$o_k]['ship_status'] = $o_v['ship_status'];      //发货状态
            $order_objects[$o_k]['is_oversold'] = $o_v['is_oversold'];   //是否超卖，true超卖；false正常
            $order_objects[$o_k]['divide_order_fee'] = $o_v['divide_order_fee'];//实付金额 必传
            $order_objects[$o_k]['bn']          = $o_v['bn'];//货品编码 新加
            $order_objects[$o_k]['sku_uuid'] = $o_v['sku_uuid'];   //sku_uuid
            $order_items = array();

            $total_pmt_price = 0;

            foreach ($o_v['order_items'] as $i_k => $i_v) {
                $order_items[$i_k]['item_type'] = $i_v['item_type'];
                $order_items[$i_k]['shop_goods_id'] = $i_v['iid'];
                $order_items[$i_k]['shop_product_id'] = $i_v['sku_id'];
                $order_items[$i_k]['bn'] = $i_v['bn'];
                $order_items[$i_k]['name'] = $i_v['name'];
                $product_attr = array();

                if (!empty($i_v['sku_properties'])) {
                    $sku_properties = explode(';', $i_v['sku_properties']);
                    foreach ($sku_properties as $si => $sp) {
                        $_sp = explode(':', $sp);
                        $product_attr[$si]['label'] = $_sp[0];
                        $product_attr[$si]['value'] = $_sp[1];
                    }
                }
                if (!empty($product_attr)) {
                    $order_items[$i_k]['original_str'] = $i_v['sku_properties'];
                }

                $order_items[$i_k]['product_attr'] = $product_attr;
                $order_items[$i_k]['quantity'] = $i_v['num'];
                $order_items[$i_k]['price'] = $i_v['price'];
                $order_items[$i_k]['amount'] = $i_v['total_item_fee'];
                $order_items[$i_k]['pmt_price'] = $i_v['discount_fee'];
                $order_items[$i_k]['sale_price'] = $i_v['sale_price'];
                $order_items[$i_k]['weight'] = $i_v['weight'];
                $order_items[$i_k]['score'] = $i_v['score'];
                $order_items[$i_k]['cost'] = $i_v['cost'];      //sku成本价
                $order_items[$i_k]['promotion_id'] = $i_v['promotion_id'];    //促销方案ID
                $order_items[$i_k]['movement_code'] = $i_v['movement_code'];

                $order_items[$i_k]['divide_order_fee'] = $i_v['divide_order_fee'];
                $order_items[$i_k]['part_mjz_discount'] = $i_v['part_mjz_discount'];

                $total_pmt_price += $i_v['discount_fee'];
            }
            $order_objects[$o_k]['order_items'] = $order_items;
            $order_objects[$o_k]['pmt_price'] = $o_v['discount_fee'] ?: $total_pmt_price;
        }
        $order_sdf['order_objects'] = $order_objects;
        //订单商品结构数组信息
        $order_sdf['lastmodify'] = $aData['lastmodify'] ? $aData['lastmodify'] : $aData['modified'];
    
        if (isset($this->__channelObj->channel['tbbusiness_type']) && $this->__channelObj->channel['tbbusiness_type'] == 'BAOZUN') {
            foreach ($order_sdf['order_objects'] as $_o_k => $_o_v) {
                $o_sale_price = bcmul($_o_v['sale_price'], $_o_v['quantity'], 2); //宝尊传的是一件商品的销售价
                $order_sdf['order_objects'][$_o_k]['sale_price'] = $o_sale_price;
            
                foreach ($_o_v['order_items'] as $_i_k => $_i_v) {
                    $i_sale_price = bcmul($_i_v['sale_price'], $_i_v['quantity'], 2);
                    $order_sdf['order_objects'][$_o_k]['order_items'][$_i_k]['sale_price'] = $i_sale_price;
                }
            }
        }
    
        $this->_ordersdf = $order_sdf;

    }

    /**
     * 创建接收
     *
     * @return void
     * @author
     **/

    protected function _canCreate()
    {
        if(!parent::_canCreate()){
            return false;
        }
        try{
            $this->_docheck('create', $this->_checkExcludeList);

        }catch (Exception $e){
            $this->__apilog['result']['msg'] = $e->getMessage();
            return false;
        }

    }

    /**
     * 更新接收
     *
     * @return void
     * @author
     **/
    protected function _canUpdate()
    {
        if (false === parent::_canUpdate()) {

            $returnSuccMsgList = ["取消订单不接收", "ERP取消订单，不做更新", "ERP发货订单，不做更新", "已发货订单不接收","完成订单不接收"];
            if(in_array($this->__apilog['result']['msg'], $returnSuccMsgList)){
                $this->__apilog['result']['rsp'] = 'succ';
            }
            return false;
        }
        try {
            $this->_docheck('update', $this->_checkExcludeList);

        } catch (Exception $e) {
            $this->__apilog['result']['msg'] = $e->getMessage();
            return false;
        }

        return true;
    }

    public function _canAccept()
    {


        $presalesetting = app::get('ome')->getConf('ome.order.presale');
        foreach($this->_ordersdf['order_objects'] as $object){
            if(app::get('presale')->is_installed() && $presalesetting == '1' && $object['zhengji_status']){
                if(in_array($object['zhengji_status'],array('1'))){
                    $this->_accept_unpayed_order = true;
                }


                if (in_array($object['zhengji_status'],array('3'))){//预售订单标识
                    $this->__apilog['result']['msg'] = '征集失败订单不收!';
                    return false;
                }
            }else{
                if (in_array($object['zhengji_status'],array('1','3'))){
                    $this->__apilog['result']['msg'] = '征集中和征集失败订单不收!';
                    return false;
                }
            }

            
        }

        if(app::get('presale')->is_installed() && $presalesetting == '1' && $this->_ordersdf['order_type'] == 'presale'){
                if(in_array($this->_ordersdf['step_trade_status'],array('FRONT_PAID_FINAL_NOPAID'))){
                    $this->_ordersdf['partpayed'] = 'true';
                    $this->_accept_unpayed_order = true;
                }

        }
        if(($this->_accept_unpayed_order==false && in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID','FRONT_PAID_FINAL_NOPAID'))) || ($this->_accept_unpayed_order == true && in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID')))){

                $this->__apilog['result']['msg'] = '定金未付尾款未付或定金已付尾款未付订单不接收';
                return false;
        }

        return parent::_canAccept();
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        if ( (in_array($this->_tgOrder['order_type'], array('presale'))) 
                && ($this->_tgOrder['pay_status'] == '3' || $this->_tgOrder['total_amount'] != $this->_ordersdf['total_amount'])
                && $this->_tgOrder['cost_item'] == $this->_ordersdf['cost_item'] 
                && $this->_ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
        {
            $plugins[] = 'payment';
            
           
        }
       
        
        return $plugins;
    }

    protected function get_update_components()
    {
        $components = parent::get_update_components();

        $items_key = array_search('items',$components);
        if($items_key){
            unset($components[$items_key]);
                    
        }
        //查看是否预售订单已支付状态
        if(in_array($this->_tgOrder['order_type'], array('presale'))){
            $components[] = 'tbpresale';
        }
        if ( (in_array($this->_tgOrder['order_type'], array('presale'))) 
                && ($this->_tgOrder['pay_status'] == '3' || $this->_tgOrder['total_amount'] != $this->_ordersdf['total_amount'])
                && $this->_tgOrder['cost_item'] == $this->_ordersdf['cost_item'] 
                && $this->_ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
            {
            if(!array_search('master', $components)) $components[] = 'master';
            $components[] = 'items';
        }

       
        return $components;
    }
    
}
