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
class erpapi_shop_matrix_taobao_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;
    /**
     * 订单obj明细唯一标识
     * 
     * @var string
     * */
    public $object_comp_key = 'bn-oid-obj_type';

    /**
     * 订单item唯一标识
     * 
     * @var string
     * */
    public $item_comp_key = 'bn-shop_product_id-item_type';


        /**
     * business_flow
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function business_flow($sdf)
    {
        if ($sdf['t_type'] == 'fenxiao' || $sdf['order_source'] == 'taofenxiao') {
            $order_type = 'b2b';
        } else {
            $order_type = 'b2c';
        }

        return 'erpapi_shop_matrix_taobao_response_order_'.$order_type;
    }

    protected function _analysis()
    {
        parent::_analysis();
        $this->_ordersdf['is_service_order'] = ($this->_ordersdf['is_service_order'] || $this->_ordersdf['service_order_objects']['service_order']);
        if($this->_ordersdf['ship_status'] == '2' && $this->_ordersdf['is_service_order']) {
            $this->_ordersdf['ship_status'] = '0';
            foreach($this->_ordersdf['order_objects'] as $object) {
                if($object['source_status'] != 'WAIT_SELLER_SEND_GOODS') {
                    $this->_ordersdf['ship_status'] = '2';
                }
            }
        }

        if (strtolower($this->_ordersdf['is_daixiao']) == 'true') {
            $this->_setPlatformDelivery();
            $this->_ordersdf['is_sh_ship'] = 'true';
            $this->_ordersdf['sh_ship_exists'] = true;
        }

        $shShip = app::get('ome')->getConf('ome.platform.order.consign');
        if($shShip == 'true') {
            $is_sh_ship_num = 0;
            foreach($this->_ordersdf['order_objects'] as $object){
                if($object['is_sh_ship'] == 'true'){
                    $is_sh_ship_num++;

                    $this->_ordersdf['sh_ship_exists'] = true;
                }
            }

            if ($is_sh_ship_num > 0 && $is_sh_ship_num == count($this->_ordersdf['order_objects'])) {
                $this->_ordersdf['order_type'] = 'platform';
                $this->_ordersdf['is_sh_ship'] = 'true';
            }
        }



        //商品总额扣掉服务费:淘宝的服务费算在总额上
        $total_fee = 0;
        if (is_array($this->_ordersdf['service_order_objects']) && is_array($this->_ordersdf['service_order_objects']['service_order'])) {
            foreach ((array)$this->_ordersdf['service_order_objects']['service_order'] as $s) {
                $total_fee += (float)$s['total_fee'];
            }
        }


        if ($total_fee>0) $this->_ordersdf['cost_item'] -= $total_fee;
        
        //发票处理
        $mdl_invoice_order_taobao = app::get('invoice')->model('order_taobao');
        $rs_invoice_order_taobao = $mdl_invoice_order_taobao->dump(array("platform_tid"=>$this->_ordersdf["order_bn"]));
        if(!empty($rs_invoice_order_taobao)){
            $this->_ordersdf["is_tax"] = 'true';
            $this->_ordersdf["tax_title"] = $rs_invoice_order_taobao["payer_name"];
            $this->_ordersdf["payer_register_no"] = $rs_invoice_order_taobao["payer_register_no"];
            $this->_ordersdf["invoice_kind"] = $rs_invoice_order_taobao["invoice_kind"];
        }
        $oidObject = [];
        $coupon = [];
        foreach ($this->_ordersdf['order_objects'] as $k => $object){
            if (!empty($object['estimate_con_time'])) {
                $arrDate = array();
                $tmpTime = 0;
                if(preg_match_all('/付款后([0-9])天内/', $object['estimate_con_time'], $arrDate)) {
                    $tmpTime = strtotime(date('Y-m-d', strtotime($arrDate[1][0].' days')));
                }
                if(preg_match_all('/[0-9]+年[0-9]+月[0-9]+日/', $object['estimate_con_time'], $arrDate)) {
                    $tmpTime = strtotime(str_replace(array('年','月','日'), '', $arrDate[0][0]));
                }
                if($tmpTime) {
                    $this->_ordersdf['latest_delivery_time'] = $tmpTime;
                    /*$this->_ordersdf['order_objects'][$k]['estimate_con_time'] = $tmpTime;
                    foreach ( $object['order_items'] as $ik => $item ){
                        $this->_ordersdf['order_objects'][$k]['order_items'][$ik]['estimate_con_time'] = $tmpTime;
                    }*/
                }
            }
            if($object['divide_order_fee'] > 0) $oidObject[$object['oid']] = $object;
            //权益金处理
            foreach ( $object['order_items'] as $itemkey => $item ){
                if($item['expand_card_expand_price_used_suborder'] > 0) {
                    $coupon[] = array(
                        'num'           => $object['quantity'],
                        'material_bn'   => $object['bn'],
                        'oid'           => $object['oid'],
                        'material_name' => $object['name'],
                        'type'          => '',
                        'type_name'     => '购物金优惠',
                        'coupon_type'   => '0',
                        'amount'        => $item['expand_card_expand_price_used_suborder'] / $object['quantity'],
                        'total_amount'  => $item['expand_card_expand_price_used_suborder'],
                        'create_time'   => kernel::single('ome_func')->date2time($this->_ordersdf['createtime']),
                        'pay_time'      => kernel::single('ome_func')->date2time($this->_ordersdf['payment_detail']['pay_time']),
                        'shop_type'     => 'taobao',
                        'source'        => 'push',
                    );
                    $this->_ordersdf['order_objects'][$k]['part_mjz_discount'] = 
                        bcadd($object['part_mjz_discount'], $item['expand_card_expand_price_used_suborder'], 2);
                    $this->_ordersdf['order_objects'][$k]['divide_order_fee'] = 
                        bcsub($object['divide_order_fee'], $item['expand_card_expand_price_used_suborder'], 2);
                    $this->_ordersdf['order_objects'][$k]['order_items'][$itemkey]['part_mjz_discount'] = 
                        bcadd($item['part_mjz_discount'], $item['expand_card_expand_price_used_suborder'], 2);
                    $this->_ordersdf['order_objects'][$k]['order_items'][$itemkey]['divide_order_fee'] = 
                        bcsub($item['divide_order_fee'], $item['expand_card_expand_price_used_suborder'], 2);
                }
            }
        }
        // 实付金额处理
        if (isset($this->_ordersdf['coupon_fee']) && floatval($this->_ordersdf['coupon_fee']) > 0 && isset($this->_ordersdf['order_objects']) && is_array($this->_ordersdf['order_objects'])) {
            $coupon_fee = floatval($this->_ordersdf['coupon_fee']/100);
            // 分摊方法
            $options = [
                'part_total' => $coupon_fee,
                'part_field' => 'coupon_fee',
                'porth_field' => 'divide_order_fee',
                'minuend_field' => 'divide_order_fee',
            ];
            $this->_ordersdf['order_objects'] = kernel::single('ome_order')->calculate_part_porth($this->_ordersdf['order_objects'], $options);
            $this->_ordersdf['coupon_actuallypay_field'] = 'calcActuallyPay';
            foreach($this->_ordersdf['order_objects'] as $k => $object) {
                $this->_ordersdf['order_objects'][$k]['calcActuallyPay'] = sprintf('%.2f', $object['divide_order_fee'] - $object['coupon_fee']);
            }
        }
        if ($this->_ordersdf['pmt_detail']) {
            foreach ((array) $this->_ordersdf['pmt_detail'] as $key => $value) {
                if (!is_array($value) || trim($value['pmt_amount']) == '' || trim($value['pmt_amount']) == 0) {
                    continue;
                }
                if($value['pmt_describe'] == '购物金优惠') {
                    continue;
                }
                $type = '';
                if(strpos($value['promotion_id'], '-')) {
                    list($type, ) = explode('-', $value['promotion_id'], 2);
                }
                if($value['kd_child_discount_fee']) {
                    foreach (explode(',', $value['kd_child_discount_fee']) as $kd) {
                        list($oid, $total_amount) = explode('|', $kd);
                        $object = $oidObject[$oid];
                        if(empty($object)) {
                            continue;
                        }
                        $coupon[] = array(
                            'num'           => $object['quantity'],
                            'material_bn'   => $object['bn'],
                            'oid'           => $object['oid'],
                            'material_name' => $object['name'],
                            'type'          => $type,
                            'type_name'     => $value['pmt_describe'],
                            'coupon_type'   => '0',
                            'amount'        => $total_amount / $object['quantity'],
                            'total_amount'  => $total_amount,
                            'create_time'   => kernel::single('ome_func')->date2time($this->_ordersdf['createtime']),
                            'pay_time'      => kernel::single('ome_func')->date2time($this->_ordersdf['payment_detail']['pay_time']),
                            'shop_type'     => 'taobao',
                            'source'        => 'push',
                        );
                    }
                } else {
                    $object = $oidObject[$value['pmt_id']];
                    if(empty($object)) {
                        continue;
                    }
                    $coupon[] = array(
                        'num'           => $object['quantity'],
                        'material_bn'   => $object['bn'],
                        'oid'           => $object['oid'],
                        'material_name' => $object['name'],
                        'type'          => $type,
                        'type_name'     => $value['pmt_describe'],
                        'coupon_type'   => '0',
                        'amount'        => $value['kd_discount_fee'] / $object['quantity'],
                        'total_amount'  => $value['kd_discount_fee'],
                        'create_time'   => kernel::single('ome_func')->date2time($this->_ordersdf['createtime']),
                        'pay_time'      => kernel::single('ome_func')->date2time($this->_ordersdf['payment_detail']['pay_time']),
                        'shop_type'     => 'taobao',
                        'source'        => 'push',
                    );
                }
            }
        }
    
        $this->_ordersdf['coupon_data']         = $coupon;
        if ($this->_ordersdf['cn_info']) {
            if ($this->_ordersdf['cn_info']['es_date']) {
                $this->_ordersdf['consignee']['r_time'] = $this->_ordersdf['cn_info']['es_date'] . ' ' . $this->_ordersdf['cn_info']['es_range'];
            }
            if ($this->_ordersdf['cn_info']['deliveryCps']) {
                $this->_ordersdf['shipping']['shipping_name'] = $this->_ordersdf['cn_info']['deliveryCps'];
            }
        
            // 送货上门
            if ($this->_ordersdf['cn_info']['asdp_biz_type'] == 'logistics_upgrade') {
                $this->_ordersdf['cpup_service'] = $this->_ordersdf['cn_info']['asdp_ads'];
                $this->_ordersdf['consignee']['r_time']       = $this->_ordersdf['cn_info']['sign_time'];
                $this->_ordersdf['promise_service'] = is_array($this->_ordersdf['cn_info']['logistics_agreement']) ? $this->_ordersdf['cn_info']['logistics_agreement']['logistics_service_msg'] : '';
                // 物流公司映射
                $shipping_name = $this->_ordersdf['cn_info']['delivery_cps'];
                if ($shipping_name) {
        
                    $corpMdl = app::get('ome')->model('dly_corp');
        
                    if ($this->__channelObj->channel['config']['cpmapping'][$shipping_name]) {
                        $shipping_name = $this->__channelObj->channel['config']['cpmapping'][$shipping_name];
                    } elseif (!in_array($shipping_name, array_keys($corpMdl->corp_default()))) {
                        $this->_ordersdf['cpuperr'] = true;
                    }
                }
                $this->_ordersdf['shipping']['shipping_name'] = $shipping_name;
            }
            
            // 闪购业务处理：判断 scenarioGroup=XSDBC，如果存在则设置 is_xsdbc 和 o2o_store_bn
            if (isset($this->_ordersdf['cn_info']) 
                && isset($this->_ordersdf['cn_info']['trade_attr']) 
                && isset($this->_ordersdf['cn_info']['trade_attr']['scenarioGroup']) 
                && $this->_ordersdf['cn_info']['trade_attr']['scenarioGroup'] == 'XSDBC') {
                
                // 设置闪购订单标记
                $this->_ordersdf['is_xsdbc'] = true;
                
                // 设置 o2o_info.o2o_store_bn
                $storeId = $this->_ordersdf['cn_info']['trade_attr']['xsdFulfillmentInfo']['storeId'];
                $this->_ordersdf['o2o_info']['o2o_store_bn'] = $storeId;
                
                // 设置所有订单商品的 store_code
                if (isset($this->_ordersdf['order_objects']) && is_array($this->_ordersdf['order_objects'])) {
                    foreach ($this->_ordersdf['order_objects'] as &$order_object) {
                        $order_object['store_code'] = $storeId;
                    }
                }
            }
        }
        
        //天猫优仓订单
        if ($this->_ordersdf['erpHold'] == '1') {
            $this->_ordersdf['is_delivery'] = 'N'; //hold单:不用审核订单,禁止发货
        }elseif($this->_ordersdf['erpHold'] == '4') {
            //$this->_ordersdf['is_delivery'] = 'N';
            //指定仓库store_code 和 物流公司shipping_name 进行发货;
        }
        if(app::get('dchain')->is_installed()){
            $shopInfo = app::get('ome')->model('shop')->dump(array('shop_id'=>$this->__channelObj->channel['shop_id']), 'shop_id,shop_bn,node_id');
            $channelInfo = app::get('channel')->model('channel')->db_dump(array('node_id'=>$shopInfo['node_id'], 'channel_type'=>'dchain','disabled'=>'false'), 'channel_id,channel_bn,config');
            $isDchain = ($channelInfo ? true : false);
            if ($isDchain && $this->_ordersdf['erpHold'] != 4) {
                foreach ((array) $this->_ordersdf['order_objects'] as $objkey => $object) {
                    $this->_ordersdf['order_objects'][$objkey]['store_code']  = '';
                }
            }
        }

        //order_source
        if ($this->_ordersdf['extend_field']['qn_distr'] == '1') {
            $this->_ordersdf['order_source'] = 'goofish';
        }

        if ($this->_ordersdf['extend_field']) {
            $this->_ordersdf['guobu_info'] = [];
            // 国补 - 淘宝直销
            if ($gov_subsidy = $this->_ordersdf['extend_field']['gov_subsidy']) {

                if ($gov_subsidy['use_gov_subsidy_new'] == 1) {

                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                    $this->_ordersdf['guobu_info']['guobu_type'][] = 1; // 支付立减

                } elseif (!$gov_subsidy['use_gov_subsidy_new'] && $gov_subsidy['gov_subsidy_amount_new']>0) {

                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                    $this->_ordersdf['guobu_info']['guobu_type'][] = 2; // 下单立减
                    $this->_ordersdf['guobu_info']['gov_subsidy_amount_new'] = $gov_subsidy['gov_subsidy_amount_new'];

                }

                if ($gov_subsidy['gov_store'] == 1 || $gov_subsidy['gov_main_subject']) {

                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                    $this->_ordersdf['guobu_info']['guobu_type'][] = 4; // 一品卖多地
                }

                if ($this->_ordersdf['guobu_info']) {

                    // 是否需要采集sn+imei码，以及需要的个数，以及是否需要校验
                    // a_b_c
                    // a：大于0的整数——表示需要采集sn码的数量
                    // b：0-5的整数——表示需要采集的imei码数量
                    // c：1/0——表示是否会对sn和imei进行校验
                    if ($gov_subsidy['gov_sn_check']) {
                        $gov_sn_check = explode('_', $gov_subsidy['gov_sn_check']);
                        if ($gov_sn_check[0]>0) {
                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0040; // 需采集SN码
                        }
                        if ($gov_sn_check[1]>0 and $gov_sn_check[1]<=5) {
                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0080; // 需采集IMEI码
                        }
                        if ($gov_sn_check[2] == 1) {
                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0100; // 需校验SN码
                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0200; // 需校验IMEI码
                        }
                    }

                    $this->_ordersdf['guobu_info']['gov_subsidy'] = $gov_subsidy;
                    // 招商皮信息 N_云闪付优惠码_折扣比例_类目编码
                    // 1）修改后第一段内容为N，将不再展示招商皮ID；
                    // 2）如果不是云闪付模式下的订单，第二段云闪付优惠码为N
                    foreach ($this->_ordersdf['order_objects'] as $k => $_object) {
                        foreach ( $_object['order_items'] as  $_item ){
                            if (isset($_item['extend_item_list']['gov_zhaoshangpi']) && $_item['extend_item_list']['gov_zhaoshangpi']) {
                                $this->_ordersdf['guobu_info']['gov_zhaoshangpi'][$_object['oid']][] = $_item['extend_item_list'];
                            }
                            // 国补 唯一码获取
                            if (isset($_item['extend_item_list']['gov_sn_check']) && $_item['extend_item_list']['gov_sn_check']) {
                                $this->_ordersdf['guobu_info']['gov_sn_check'][$_object['oid']][] = $_item['extend_item_list']['gov_sn_check'];
                            }
                        }
                    }

                }
            }
            // 国补 - 淘宝分销
            if ($features = $this->_ordersdf['extend_field']['features']) {
                foreach ($features['feature'] as $attr) {
                    if ($attr['attr_key'] == '_F_zfbt_06' && $attr['attr_value']) {

                        $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                        $this->_ordersdf['guobu_info']['guobu_type'][] = 1; // 支付立减 (临时给支付立减类型)

                    } elseif ($attr['attr_key'] == 'gov_main_subject' && $attr['attr_value']) {
                        
                        $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                        $this->_ordersdf['guobu_info']['guobu_type'][] = 4; // 一品卖多地
                    }
                }

                if ($this->_ordersdf['guobu_info']) {

                    foreach ($features['feature'] as $attr) {
                        if ($attr['attr_key'] == '_F_zfbt_sn' && $attr['attr_value']) {
                            $_F_zfbt_sn = explode('_', $attr['attr_value']);
                            if ($_F_zfbt_sn[0]>0) {
                                $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0040; // 需采集SN码
                            }
                            if ($_F_zfbt_sn[1]>0 and $_F_zfbt_sn[1]<=5) {
                                $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0080; // 需采集IMEI码
                            }
                            if ($_F_zfbt_sn[2] == 1) {
                                $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0100; // 需校验SN码
                                $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0200; // 需校验IMEI码
                            }
                            break;
                        }
                    }

                    $this->_ordersdf['guobu_info']['features'] = $features; 

                    foreach ($this->_ordersdf['order_objects'] as $k => $_object) {
                        foreach ( $_object['order_items'] as  $_item ){
                            if (isset($_item['extend_item_list']['features']) && $_item['extend_item_list']['features']) {
                                $this->_ordersdf['guobu_info']['features_items'][$_object['oid']][] = $_item['extend_item_list']['features'];
                            }
                        }
                    }
                }
            }
            // 国补 - 淘宝供应链(ASCP)
            if ($extraContent = $this->_ordersdf['extend_field']['extraContent']) {
                $extraContent = array_filter(explode(';', $extraContent));
                foreach ($extraContent as $_ec) {
                    list($_ec_key, $_ec_value) = explode(':', $_ec);
                    if ($_ec_key == '_F_zfbt_06' && $_ec_value == 1) {
                        $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                        $this->_ordersdf['guobu_info']['guobu_type'][] = 1; // 支付立减 (临时给支付立减类型)
                    }
                }
                if ($this->_ordersdf['guobu_info']) {

                    $this->_ordersdf['guobu_info']['extraContent'] = $extraContent; 

                    foreach ($this->_ordersdf['order_objects'] as $k => $_object) {
                        foreach ( $_object['order_items'] as  $_item ){
                            if (isset($_item['extend_item_list']['expProps']) && $expProps = $_item['extend_item_list']['expProps']) {

                                $expProps = explode(';', $expProps);
                                foreach ($expProps as $expProp) {
                                    list($expProp_key, $expProp_value) = explode(':', $expProp);
                                    if ($expProp_key == '_F_zfbt_sn' && $expProp_value) {
                                        $_F_zfbt_sn = explode('_', $expProp_value);
                                        if ($_F_zfbt_sn[0]>0) {
                                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0040; // 需采集SN码
                                        }
                                        if ($_F_zfbt_sn[1]>0) {
                                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0080; // 需采集IMEI码
                                        }
                                        if ($_F_zfbt_sn[2] == 1) {
                                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0100; // 需校验SN码
                                            $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0200; // 需校验IMEI码
                                        }
                                        break;
                                    }
                                }
                                $this->_ordersdf['guobu_info']['expProps'][$_object['oid']][] = $_item['extend_item_list']['expProps'];
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        ///////////////////////////////////////////
        // 解决订单备注没更新(淘宝平台问题，备注修改订单最后时间不变),
        // 同时防止比较明细，失败订单恢复后又重新更新为失败订单
        ///////////////////////////////////////////
        $memochg = false;

        if ($this->_tgOrder) {
            $last_custom_mark = array();$last_mark_text=array();
            $custom_mark = array(); 
            if ($this->_tgOrder['custom_mark'] && is_string($this->_tgOrder['custom_mark'])) {
                $custom_mark = unserialize($this->_tgOrder['custom_mark']);
            }

            $mark_text = array();
            if ($this->_tgOrder['mark_text'] && is_string($this->_tgOrder['mark_text'])) {
                $mark_text = unserialize($this->_tgOrder['mark_text']);
            }

            foreach ((array) $custom_mark as $key => $value) {
                if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

                if ( intval($value['op_time']) > intval($last_custom_mark['op_time']) ) {
                    $last_custom_mark = $value;
                }
            }

            foreach ((array) $mark_text as $key => $value) {
                if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

                if ( intval($value['op_time']) > intval($last_mark_text['op_time']) ) {
                    $last_mark_text = $value;
                }
            }

            if ( ($this->_ordersdf['custom_mark'] && $this->_ordersdf['custom_mark'] != $last_custom_mark['op_content']) || 
                 ($this->_ordersdf['mark_text'] && $this->_ordersdf['mark_text'] != $last_mark_text['op_content'] ) ) {
                $memochg = true;
            }
        }

        ///////////////////////////////////////////
        // 解决订单地址修改不更新(淘宝平台问题，订单地址修改最后时间不变),
        ///////////////////////////////////////////
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (!$this->_operationSel && $this->_tgOrder && $lastmodify >= $this->_tgOrder['outer_lastmodify']) {
            $consignee = array();
            $area = $this->_ordersdf['consignee']['area_state'] . '/' . $this->_ordersdf['consignee']['area_city'] . '/' . $this->_ordersdf['consignee']['area_district'];
            kernel::single('ome_func')->region_validate($area);

            $consignee['area']      = $area;
            $consignee['name']      = $this->_ordersdf['consignee']['name'];
            $consignee['addr']      = $this->_ordersdf['consignee']['addr'];
            $consignee['telephone'] = $this->_ordersdf['consignee']['telephone'];
            $consignee['mobile']    = $this->_ordersdf['consignee']['mobile'];
            
            $diff_consignee = array_diff_assoc(array_filter($consignee), $this->_tgOrder['consignee']);
            if ($diff_consignee) $memochg = true;
        }

        // 即不是更新，也是不是创建,才做这样逻辑判断
        if (!$this->_operationSel && $memochg) {
            $this->_operationSel = 'update';
        }

    }

    /**
     * _canAccept
     * @return mixed 返回值
     */
    public function _canAccept()
    {
        if($this->__channelObj->channel['business_type']=='zx' && in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->__apilog['result']['msg'] = '直销店铺不接收分销订单';
            return false;
        }

        if($this->__channelObj->channel['business_type']=='fx' && !in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->__apilog['result']['msg'] = '分销店铺不接收直销订单';
            return false;
        }

        foreach($this->_ordersdf['order_objects'] as $object){
            if (in_array($object['zhengji_status'],array('1','3'))){
                $this->__apilog['result']['msg'] = '征集中和征集失败订单不收!';
                return false;
            }

            // if(!empty($object['is_sh_ship']) && $this->_ordersdf['order_type'] != 'platform'){
            //     if($object['is_sh_ship'] == 'true'){
            //         $this->__apilog['result']['msg'] = '菜鸟自动流转订单,不接受';
            //         return false;
            //     }
            // }
        }

        if ($this->_ordersdf['is_sh_ship'] == 'true' && $this->_ordersdf['order_type'] != 'platform') {
            $this->__apilog['result']['msg'] = '菜鸟自动流转订单,不接受';
            return false;
        }

        if(in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID','FRONT_PAID_FINAL_NOPAID'))){
            $this->__apilog['result']['msg'] = '定金未付尾款未付或定金已付尾款未付订单不接收';
            return false;
        }

        if($this->_ordersdf['other_list']){
            foreach((array) $this->_ordersdf['other_list'] as $val){
                // 淘宝处方类订单
                if($val['type'] == 'rx_audit' && $val['rx_audit_status'] == '0'){
                    $this->__apilog['result']['msg'] = '处方药未审核状态，不接受';
                    return false;
                }
            }
        }

        return parent::_canAccept();
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'tbgift';
        $plugins[] = 'coupon';
        // $plugins[] = 'tbjz';
        
        //[翱象系统]获取订单详情
        if($this->_ordersdf['cn_info']['asdp_biz_type'] == 'aox' || $this->_ordersdf['cn_info']['logistics_agreement']['asdp_biz_type'] == 'aox'){
            //$plugins[] = 'orderextend'; //公共类中已经定义
            $plugins[] = 'orderdetial';
        }
        
        return $plugins;
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        //判断如果是已完成只更新时间
        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['end_time']>0){
            $plugins = array();
            $plugins[] = 'confirmreceipt';
        }

        if (false === array_search('ordertype', $plugins)) {
            $plugins[] = 'ordertype';
        }
        
        //预售订单付尾款后才有天猫物流升级的信息
        if ( (in_array($this->_tgOrder['order_type'], array('presale')))
            && ($this->_tgOrder['pay_status'] == '3' || $this->_tgOrder['total_amount'] != $this->_ordersdf['total_amount'])
            && $this->_tgOrder['cost_item'] == $this->_ordersdf['cost_item']
            && $this->_ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
        {
            if ($this->_ordersdf['cn_info']['asdp_biz_type'] == 'logistics_upgrade') {
                $plugins[] = 'orderextend';
            }
        }
        
        //[翱象系统]获取订单扩展信息
        if($this->_ordersdf['cn_info']['asdp_biz_type'] == 'aox' || $this->_ordersdf['cn_info']['logistics_agreement']['asdp_biz_type'] == 'aox'){
            $plugins[] = 'orderextend';
        }
        
        return $plugins;
    }

    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype','oversold');

        $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
        // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id', array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        $items_flag = false;
        foreach($this->_ordersdf['order_objects'] as $objects){
            foreach($this->_tgOrder['order_objects'] as $tgobjects){
                if($objects['oid'] == $tgobjects['oid'] && $tgobjects['delete'] == 'false'){
                    if($tgobjects["quantity"] == $objects["quantity"] && $tgobjects["price"] == $objects["price"] && $tgobjects["bn"] != $objects["bn"]){
                        $items_flag = true;
                    }
                }
            }
        }
        if($items_flag && $this->_tgOrder['is_modify']!='true'){
            $components[] = 'items';
        }

        if($this->_tgOrder['status']=='finish'){
            $components = [];
        }

        return $components;
    }

    protected function get_convert_components()
    {
        $components = parent::get_convert_components();
        $components[] = 'oversold';
        return $components;
    }
}
