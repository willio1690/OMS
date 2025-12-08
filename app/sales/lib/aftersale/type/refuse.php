<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_aftersale_type_refuse
{
    /**
     * generate_aftersale
     * @param mixed $reship_id ID
     * @return mixed 返回值
     */
    public function generate_aftersale($reship_id = null)
    {
        if (empty($reship_id)) {
            return false;
        }

        $Omembers   = app::get('ome')->model('members');
        $Oorder     = app::get('ome')->model('orders');
        $Oaftersale = app::get('sales')->model('aftersale');
        $Oshop      = app::get('ome')->model('shop');
        $Oreship    = app::get('ome')->model('reship');
        $Opam       = app::get('pam')->model('account');
        $salesMdl   = app::get('ome')->model('sales');

        $reshipData = $Oreship->getList('*', array('reship_id' => $reship_id), 0, 1);

        $shopData   = $Oshop->getList('name,shop_bn', array('shop_id' => $reshipData[0]['shop_id']), 0, 1);
        $orderData  = $Oorder->getList('member_id,order_bn,platform_order_bn,betc_id,cos_id', array('order_id' => $reshipData[0]['order_id']), 0, 1);
        $shopData   = $Oshop->getList('name,shop_bn', array('shop_id' => $reshipData[0]['shop_id']), 0, 1);
        $memberData = $Omembers->getList('uname', array('member_id' => $orderData[0]['member_id']), 0, 1);
        $pamData    = $Opam->getList('login_name', array('account_id' => $reshipData[0]['op_id']), 0, 1);
        
        //归档订单
        $is_archive = kernel::single('archive_order')->is_archive($reshipData[0]['source']);
        if ($is_archive) {
            $archive_ordObj = kernel::single('archive_interface_orders');
            $orderData      = $archive_ordObj->getOrders($reshipData[0]['order_id'], 'member_id,order_bn');
            $orderData[0]   = $orderData;
        }
        
        //销售单
        $salesInfo = $salesMdl->db_dump(['order_id'=>$reshipData[0]['order_id']],'sale_id,order_id,ship_time');

        $data['shop_id']        = $reshipData[0]['shop_id'];
        $data['shop_bn']        = $shopData[0]['shop_bn'];
        $data['shop_name']      = $shopData[0]['name'];
        $data['order_id']       = $reshipData[0]['order_id'];
        $data['order_bn']       = $orderData[0]['order_bn'];
        $data['reship_id']      = $reshipData[0]['reship_id'];
        $data['reship_bn']      = $reshipData[0]['reship_bn'];
        $data['return_type']    = 'refuse';
        $data['member_uname']   = $memberData[0]['uname'];
        $data['ship_mobile']    = $reshipData[0]['ship_mobile'];
        $data['check_op_id']    = $reshipData[0]['op_id'];
        $data['check_op_name']  = $pamData[0]['login_name'];
        $data['check_time']     = $reshipData[0]['t_end'];
        $data['org_id']         = $reshipData[0]['org_id'];
        $data['aftersale_time'] = time();
        $data['platform_order_bn']  = $orderData[0]['platform_order_bn'];
        $data['ship_time']      = $salesInfo['ship_time'];//发货时间
        
        $data['betc_id'] = $orderData[0]['betc_id'];
        $data['cos_id'] = $orderData[0]['cos_id'];
        $Oreship_items  = app::get('ome')->model('reship_items');
        $Obranch        = app::get('ome')->model('branch');
        $reshipitemData = $Oreship_items->getList('*', array('reship_id' => $reship_id, 'is_del' => 'false'));
        $branch_datas   = $Obranch->getList('name,branch_id');

        foreach ($branch_datas as $v) {
            $branch_data[$v['branch_id']] = $v['name'];
        }

        unset($branch_datas);
        if ($is_archive) {
            $data['archive'] = '1';
        }
        
        //获取退货关联的订单明细
        $aftersaleLib = kernel::single('sales_aftersale');
        $returnItems = $aftersaleLib->getReturnOrderItems($reshipData[0]['order_id'], $is_archive);
        
        //items
        foreach ($reshipitemData as $k => $v)
        {
            $order_item_id = intval($v['order_item_id']);
            $product_id = $v['product_id'];
            
            //items
            $data['aftersale_items'][$k]['bn']           = $v['bn'];
            $data['aftersale_items'][$k]['product_name'] = $v['product_name'];
            $data['aftersale_items'][$k]['num']          = $v['num'];
            $data['aftersale_items'][$k]['price']        = $v['price'];
            $data['aftersale_items'][$k]['branch_name']  = $branch_data[$v['branch_id']];
            $data['aftersale_items'][$k]['branch_id']    = $v['branch_id'];
            $data['aftersale_items'][$k]['product_id']   = $v['product_id'];
            $data['aftersale_items'][$k]['saleprice']    = $v['price'] * $v['num'];
            $data['aftersale_items'][$k]['return_type']  = $v['return_type'];
            
            //关联的销售物料
            $returnItemInfo = ($returnItems['items'][$order_item_id] ? $returnItems['items'][$order_item_id] : $returnItems['products'][$product_id]);
            $aftersale_items[$k]['item_type'] = $returnItemInfo['item_type']; //物料类型
            $aftersale_items[$k]['sales_material_bn'] = $returnItemInfo['goods_bn']; //销售物料编码
            $data['aftersale_items'][$k]['addon']         = json_encode(['shop_goods_id' => $returnItemInfo['shop_goods_id'], 'shop_product_id' => $returnItemInfo['shop_product_id']]);
        }
        
        return $data;
    }
}
