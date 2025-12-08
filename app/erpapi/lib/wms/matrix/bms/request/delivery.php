<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * BMS发货单
 *
 * @author sunjing@shopex.cn
 * @time 2017/12/7 11:48:33
 */
class erpapi_wms_matrix_bms_request_delivery extends erpapi_wms_request_delivery
{


    /**
     * 创建
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function create($sdf){

        return true;
        // 防重复发货，不推淘系订单
        if ($sdf['shop_info'] == 'taobao') {
            return $this->error('防重复发货，淘系订单不支持接口推送');
        }

        parent::create($sdf);
    }

    /**
     * 发货单创建接口名
     * 
     * @return void
     * @author 
     */
    protected function _get_create_api_name()
    {
        return WMS_BMS_ORDER_CREATE;
    }

    protected function _format_create_params($sdf){
        // 发票信息
        if ($sdf['is_order_invoice'] == 'true' && $sdf['is_wms_invoice'] == 'true'){
            $invoice       = $sdf['invoice'];
            $is_invoice    = 'true';
            $invoice_type  = $invoice_type_arr[$invoice['invoice_type']]; // ?什么情况
            $invoice_title = $invoice['invoice_title']['title'];

            // 增值税抬头信息
            if ($invoice['invoice_type'] == 'increment'){
                $invoice_info = array(
                    'name'         => $invoice['invoice_title']['uname'],
                    'phone'        => $invoice['invoice_title']['tel'],
                    'address'      => $invoice['invoice_title']['reg_addr'],
                    'taxpayer_id'  => $invoice['invoice_title']['identify_num'],
                    'bank_name'    => $invoice['invoice_title']['bank_name'],
                    'bank_account' => $invoice['invoice_title']['bank_account'],
                );
                $invoice_info = json_encode($invoice_info);
            }

            // 发票明细
            if ($invoice['invoice_items']){
                $invoice_items = array();
                $i_money = 0;
                foreach ($invoice['invoice_items'] as $val){
                    $price = round($val['money'],2);
                    $invoice_items[] = array(
                        'name'     => $val['item_name'],
                        'spec'     => $val['spec'],
                        'quantity' => $val['nums'],
                        'price'    => $price,
                    );
                    $i_money += $price;
                }
            }

            if ($invoice['content_type'] == 'items'){
                $invoice_item  = json_encode($invoice_items);
                $invoice_money = $i_money;
            }else{
                $invoice_desc  = $invoice['invoice_desc'];
                $invoice_money = round($invoice['invoice_money'],2);
            }
        }
        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'],$sdf['shop_code']);
        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        // 以下金额单位都是分
        $params = array(
            'tid'               => $sdf['outer_delivery_bn'],
            'shop_code'         => $shop_code ,
            'created'           => $create_time,
            'pay_time'          => date('Y-m-d H:i:s', $sdf['pay_time']),
            'buyer_nick'        => '',
            'receiver_country'  => '中国',
            'receiver_state'    => $sdf['consignee']['province'],
            'receiver_city'     => $sdf['consignee']['city'],
            'receiver_district' => $sdf['consignee']['district'],
            'receiver_address'  => $sdf['consignee']['addr'],
            'receiver_zip'      => $sdf['consignee']['zip'] ? $sdf['consignee']['zip'] : '000000',
            'receiver_name'     => $sdf['consignee']['name'],
            'receiver_phone'    => $sdf['consignee']['telephone'],
            'receiver_mobile'   => $sdf['consignee']['mobile'],
            'is_invoice'        => $is_invoice == 'true' ? 'true' : 'false',
            'invoice_type'      => $invoice_type,
            'invoice_title'     => $invoice_title,
            'invoice_amount'    => $invoice_money*100,
            'is_cod'            => $sdf['is_cod'],
            'order_amount'      => $sdf['total_amount'] * 100,
            'paied_amount'      => $sdf['total_amount'] * 100,
            'wait_pay_amount'   => $sdf['total_amount'] * 100,         // 应收金额
            'discount_fee'      => $sdf['discount_fee'] * 100,
            'post_fee'          => 0,
            'cod_fee'           => 0,     // COD服务费
            'buyer_message'     => $sdf['print_remark'] ? json_encode($sdf['print_remark']) : '',
            'seller_memo'       => '',//$itemCountData['seller_message'], //暂时先不传，绿森要求保密
            'receiver_town'     => '',
        );

        $params['receiver_city']     = $this->_formate_receiver_city($params['receiver_state'],$params['receiver_city']);
        $params['receiver_district'] = $this->_formate_receiver_district($params['receiver_state'],$params['receiver_city'],$params['receiver_district']);

        $items = array();
        $delivery_items = $sdf['delivery_items'];
        if ($delivery_items){
            sort($delivery_items);
            foreach ($delivery_items as $k => $v){
                $items[] = array(
                    'sub_trade_id'  => $v['order_bn'].'_'.$k,
                    'item_code'     => $v['bn'],
                    'item_name'     => $v['product_name'],
                    'num'           => (int)$v['number'],
                    'price'         => (float)$v['price'],

                    'total_fee'     => (float)$v['sale_price'] * 100,//成交额
                    'discount_fee'  => 0,
                );
            }
        }
        
        $params['items'] = json_encode($items);
        return $params;
    }
}