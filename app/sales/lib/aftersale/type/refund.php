<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_aftersale_type_refund
{
    /**
     * generate_aftersale
     * @param mixed $refund_id ID
     * @return mixed 返回值
     */
    public function generate_aftersale($refund_id = null)
    {
        if (empty($refund_id)) {
            return false;
        }
        
        $Oorder         = app::get('ome')->model('orders');
        $Oorder_items   = app::get('ome')->model('order_items');
        $Oshop          = app::get('ome')->model('shop');
        $Omembers       = app::get('ome')->model('members');
        $Orefund_apply  = app::get('ome')->model('refund_apply');
        $payment_cfgObj = app::get('ome')->model('payment_cfg');
        $Orefunds       = app::get('ome')->model('refunds');
        $Opam           = app::get('pam')->model('account');
        $Oreship        = app::get('ome')->model('reship');

        $apply_detail = $Orefund_apply->getList('*', array('apply_id' => $refund_id), 0, 1);

        //如果memo有退换货单号 说明是这个退款单是从退换货产生 故走退换货生成售后单流程
        $is_archive = kernel::single('archive_order')->is_archive($apply_detail[0]['source']);
        if ($is_archive) {
            $archive_ordObj = kernel::single('archive_interface_orders');
            $orderData      = $archive_ordObj->getOrders($apply_detail[0]['order_id'], 'member_id,order_bn,ship_mobile');
            $orderData[0]   = $orderData;
        } else {
            $orderData = $Oorder->getList('member_id,order_bn,ship_mobile,betc_id,cos_id', array('order_id' => $apply_detail[0]['order_id']), 0, 1);
        }

        if ($apply_detail[0]['reship_id'] > 0) {
            $reshipData[0]['reship_id'] = $apply_detail[0]['reship_id'];
        } else {
            preg_match_all('/\d{9,18}/', $apply_detail[0]['memo'], $output);

            $reship_bn = (count($output[0]) > 1) ? $output[0][1] : $output[0][0];

            $reshipData = $Oreship->getList('reship_id', array('reship_bn' => $reship_bn));
        }

        //如果退款申请拒绝,并且不存在退换货单
        if (empty($reshipData[0]['reship_id']) && $apply_detail[0]['status'] == '3') {
            return false;
        }

        if ($reshipData[0]['reship_id']) {
            
            unset($apply_detail);
            
            $data = kernel::single('sales_aftersale_type_change')->generate_aftersale($reshipData[0]['reship_id']);
            return $data;
        }

        $shopData   = $Oshop->getList('name,shop_bn', array('shop_id' => $apply_detail[0]['shop_id']), 0, 1);
        $memberData = $Omembers->getList('uname', array('member_id' => $orderData[0]['member_id']), 0, 1);
        $pamData    = $Opam->getList('login_name', array('account_id' => $apply_detail[0]['verify_op_id']), 0, 1);

        if ($apply_detail[0]['payment']) {
            $payment_cfgObj = app::get('ome')->model('payment_cfg');
            $payment_cfg    = $payment_cfgObj->dump(array('id' => $apply_detail[0]['payment']), 'custom_name');
            $paymethod      = $payment_cfg['custom_name']; //支付方式  varchar
        } else {
            $refund_detail = $Orefunds->getList('paymethod', array('refund_bn' => $apply_detail[0]['refund_apply_bn']));
            $paymethod     = $refund_detail[0]['paymethod'];
        }

        $data['shop_id']            = $apply_detail[0]['shop_id'];
        $data['shop_bn']            = $shopData[0]['shop_bn'];
        $data['shop_name']          = $shopData[0]['name'];
        $data['order_id']           = $apply_detail[0]['order_id'];
        $data['order_bn']           = $orderData[0]['order_bn'];
        $data['return_apply_id']    = $refund_id;
        $data['return_apply_bn']    = $apply_detail[0]['refund_apply_bn'];
        $data['return_type']        = 'refund';
        $data['refundmoney']        = $apply_detail[0]['refunded'];
        $data['refund_apply_money'] = $apply_detail[0]['money'];
        $data['real_refund_amount'] = $apply_detail[0]['real_refund_amount'] ? : 
                                            ($data['refund_apply_money'] ? : $data['refundmoney']);
        $data['paymethod']          = $paymethod;
        $data['member_id']          = $orderData[0]['member_id'];
        $data['member_uname']       = $memberData[0]['uname'];
        $data['ship_mobile']        = $orderData[0]['ship_mobile'];
        $data['refundtime']         = $apply_detail[0]['last_modified'];
        $data['refund_op_id']       = $apply_detail[0]['verify_op_id']; #name
        $data['refund_op_name']     = $pamData[0]['login_name']; #name
        $data['aftersale_time']     = time();

        $data['refund_apply_time'] = $apply_detail[0]['create_time'];
        $data['pay_type']          = $apply_detail[0]['pay_type'];
        $data['account']           = $apply_detail[0]['account'];
        $data['bank']              = $apply_detail[0]['bank'];
        $data['pay_account']       = $apply_detail[0]['pay_account'];
        $data['org_id']            = $apply_detail[0]['org_id'];
        
        //贸易公司
        $data['betc_id'] = $orderData[0]['betc_id'];
        $data['cos_id'] = $orderData[0]['cos_id'];

        if ($is_archive) {
            $data['archive'] = '1';
        }
        
        //获取退货关联的订单明细
        $aftersaleLib = kernel::single('sales_aftersale');
        $returnItems = $aftersaleLib->getReturnOrderItems($apply_detail[0]['order_id'], $is_archive);
        $saleInfo = app::get('ome')->model('sales')->db_dump(['order_id' => $apply_detail[0]['order_id']], 'sale_id,ship_time');
        $data['ship_time'] = $saleInfo['ship_time'];
        $sale_id = $saleInfo['sale_id'];
        $saleItems = app::get('ome')->model('sales_items')->getList('product_id,nums,platform_amount,settlement_amount,platform_pay_amount,actually_amount', ['sale_id'=>$sale_id]);
    
        $orderItems = $Oorder_items->getList('product_id,bn,item_id,obj_id,shop_goods_id,shop_product_id', array('order_id' => $apply_detail[0]['order_id']));
        $orderItems = array_column($orderItems,null,'product_id');
        
        $productSaleAmount = [];
        $productSendnum = [];
        foreach($saleItems as $v) {
            $productSendnum[$v['product_id']] += $v['nums'];
            $productSaleAmount[$v['product_id']]['platform_amount'] += $v['platform_amount'];
            $productSaleAmount[$v['product_id']]['settlement_amount'] += $v['settlement_amount'];
            $productSaleAmount[$v['product_id']]['actually_amount'] += $v['actually_amount'];
            $productSaleAmount[$v['product_id']]['platform_pay_amount'] += $v['platform_pay_amount'];
        }
        // 退款明细
        $aftersale_items = array();
        $product_data = $apply_detail[0]['product_data'] ? @unserialize($apply_detail[0]['product_data']) : array();
        $data['platform_amount'] = 0;
        $data['settlement_amount'] = 0;
        $data['actually_amount'] = 0;
        $data['platform_pay_amount'] = 0;
        foreach ($product_data as $k => $v)
        {
            $product_id = $v['product_id'];
            
            //关联的销售物料
            $returnItemInfo = $returnItems['products'][$product_id];
            $orderItemInfo    = $orderItems[$product_id];
            if($productSendnum[$product_id]) {
                $platform_amount = sprintf("%.2f", $productSaleAmount[$product_id]['platform_amount'] * $v['num'] / $productSendnum[$product_id]);
                $settlement_amount = sprintf("%.2f", $productSaleAmount[$product_id]['settlement_amount'] * $v['num'] / $productSendnum[$product_id]);
                $platform_pay_amount = sprintf("%.2f", $productSaleAmount[$product_id]['platform_pay_amount'] * $v['num'] / $productSendnum[$product_id]);
                $actually_amount = sprintf("%.2f", $productSaleAmount[$product_id]['actually_amount'] * $v['num'] / $productSendnum[$product_id]);
            } else {
                $platform_amount = 0;
                $settlement_amount = 0;
                $platform_pay_amount = 0;
                $actually_amount = 0;
            }
            $data['platform_amount'] += $platform_amount;
            $data['settlement_amount'] += $settlement_amount;
            $data['platform_pay_amount'] += $platform_pay_amount;
            $data['actually_amount'] += $actually_amount;
            //items
            $aftersale_items[] = array(
                'bn'            => $v['bn'],
                'product_name'  => $v['name'],
                'product_id'    => $v['product_id'],
                'num'           => $v['num'],
                'price'         => $v['price'],
                'saleprice'     => bcmul((float)$v['price'], (float)$v['num'], 2),
                'return_type'   => 'refunded',
                'money'         => bcmul((float)$v['price'], (float)$v['num'], 2),
                'refunded'      => bcmul((float)$v['price'], (float)$v['num'], 2),
                'create_time'   => time(),
                'last_modified' => time(),
                'item_type' => $returnItemInfo['item_type'], //物料类型
                'sales_material_bn' => $returnItemInfo['goods_bn'], //销售物料编码
                'addon'         => json_encode(['shop_goods_id' => $orderItemInfo['shop_goods_id'], 'shop_product_id' => $orderItemInfo['shop_product_id']],JSON_UNESCAPED_UNICODE),
                'platform_amount' => $platform_amount,
                'settlement_amount' => $settlement_amount,
                'platform_pay_amount' => $platform_pay_amount,
                'actually_amount' => $actually_amount
            );
        }
        
        $data['aftersale_items'] = $aftersale_items;
        
        return $data;
    }
}
