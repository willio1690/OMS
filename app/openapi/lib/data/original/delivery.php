<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_delivery{

    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($filter,$offset=0,$limit=100){

        $db = kernel::database();
        $shopObj = app::get('ome')->model('shop');
        $memberObj = app::get('ome')->model('members');
        $orderObj = app::get('ome')->model('orders');
        $opObj = app::get('desktop')->model('users');
        $deliveryObj = app::get('ome')->model('delivery');
        
        $deliveryLib = kernel::single('console_delivery');
        
        $logiStatus = array(
          '0' => '无',
          '1' => '已揽收',
          '2' => '在途中',
          '3' => '已签收',
          '4' => '退件/问题件',
          '5' => '待取件',
          '6' => '待派件',
        );
        
        $where[] = "d.status not in('cancel','back','stop','return_back') AND d.parent_id=0";
        if ($filter['create_starttime']) {
            $where[] = " d.create_time>=".strtotime($filter['create_starttime']);
        }
        if ($filter['create_endtime']) {
            $where[] = " d.create_time<".strtotime($filter['create_endtime']);
        }
        if ($filter['ship_starttime']) {
            $where[] = " d.delivery_time>=".strtotime($filter['ship_starttime']);
        }
        if ($filter['ship_endtime']) {
            $where[] = " d.delivery_time<".strtotime($filter['ship_endtime']);
        }
        if ($filter['branch_name']) {
            $branch_id = $this->getBranch($filter['branch_name']);
            $where[] = " d.branch_id=".$branch_id;
        }
        if ($filter['shop_name']) {
            $shop_id = $this->getShop($filter['shop_name']);

            $where[] = " d.shop_id='$shop_id'";
        }
        if ($filter['receive_area']) {
            $where[] = " d.ship_area like '".$filter['receive_area']."%'";
        }
        if ($filter['corp_name']) {
            $logi_id = $this->getCrop($filter['corp_name']);
            $where[] = " d.logi_id=".$logi_id;
        }
        $sql = 'SELECT count(1) as _c FROM sdb_ome_delivery AS d WHERE ' . implode(' AND ', $where);

        $count = $db->selectrow($sql);
        if(intval($count['_c']) >0){
            
            //是否有[京东一件代发WMS仓储]
            $yjdf_branchs = array();
            $wms_sql = "SELECT a.channel_id,b.branch_id FROM sdb_channel_channel AS a LEFT JOIN sdb_ome_branch AS b ON a.channel_id=b.wms_id ";
            $wms_sql .= " WHERE a.channel_type='wms' AND a.node_type='yjdf'";
            $wmsList = $db->select($wms_sql);
            if($wmsList){
                foreach ($wmsList as $key => $val){
                    $wms_branch_id = $val['branch_id'];
                    
                    if(empty($wms_branch_id)){
                        continue;
                    }
                    
                    $yjdf_branchs[$wms_branch_id] = $wms_branch_id;
                }
            }
            
            //所有店铺和仓库
            $shopInfos = $this->getShopinfo();
            $branchInfos = $this->getBranchinfo();
            $fields = "d.shop_id,d.branch_id,d.member_id,d.delivery_bn,d.ship_name,d.ship_province,d.ship_city,d.ship_district,d.ship_addr,d.ship_zip,d.ship_tel,d.ship_mobile";
            $fields .= ",d.ship_email,d.op_id,d.delivery_id,d.ship_tel,d.ship_email,d.logi_no,d.logi_name,d.delivery_cost_actual,d.delivery_time,d.create_time,d.logi_status,d.sign_time";
            $deliveryLists = $db->select("SELECT ". $fields ." FROM sdb_ome_delivery AS d WHERE " . implode(' AND ', $where) ." order by d.create_time asc limit ".$offset.",".$limit."");
            
            $deliveryInfos = array();
            $deliveryIds = array();
            $memberIds = array();
            
            $opIds = array();
            foreach ($deliveryLists as $k => $delivery){
                $deliveryIds[] = $delivery['delivery_id'];
                if(intval($delivery['member_id'])>0 && !in_array($delivery['member_id'],$memberIds)){
                    $memberIds[] = $delivery['member_id'];
                }
                if(intval($delivery['op_id'])>0 && !in_array($delivery['op_id'],$opIds)){
                    $opIds[] = $delivery['op_id'];
                }
            }
            
            $member_arr = $memberObj->getList('member_id,name',array('member_id'=>$memberIds),0,-1);
            foreach ($member_arr as $k => $member){
                $memberInfos[$member['member_id']] = $member['name'];
            }

            $op_arr = $opObj->getList('user_id,name',array('user_id'=>$opIds),0,-1);
            foreach ($op_arr as $k => $op){
                $opInfos[$op['user_id']] = $op['name'];
            }

            $sale_ordersInfos = array();
            foreach ( $deliveryLists as $delivery ) {
                $orderIds = array();
                $delivery_id = $delivery['delivery_id'];
                $branch_id = $delivery['branch_id'];
                
                $orderIds= $this->getOrderIdByDeliveryIdForOpenApi($delivery_id);
                $odIds = $this->getOrderId($orderIds);
                $order_arr = $orderObj->getList('order_bn,mark_text,custom_mark,cost_freight',array('order_id'=>$odIds),0,-1);
                $orderInfos = array();
                $markInfos = array();
                $customInfos = array();
                
                foreach ($order_arr as $k => $order){
                    $orderInfos[] = $order['order_bn'];
                    $mark_text = $order['mark_text']!='' ? unserialize($order['mark_text']) : '';
                    $markInfos[$order['order_bn']] = $mark_text;
                    $custom_mark = $order['custom_mark']!='' ? unserialize($order['custom_mark']) : '';
                    $customInfos[$order['order_bn']] = $custom_mark;
                    $cost_freight=$order['cost_freight'];
                }
                
                $mark_memo = '';
                if ($markInfos) {
                    foreach ($markInfos as $mk=>$infos ) {
                        $mark_memo.="(订单号".$mk.")";
                        foreach ($infos as $info ) {
                            $mark_memo.=','.$info['op_content'];
                        }
                    }
                }
                unset($markInfos);
                
                $custom_memo = '';
                if ($customInfos) {
                    foreach ($customInfos as $cust=>$infos ) {
                        $custom_memo.="(订单号:".$cust.")";
                        foreach ($infos as $info ) {
                            $custom_memo.=','.$info['op_content'];
                        }
                    }
                }
                unset($customInfos);
                unset($order_arr);
                
                $orderInfos = $orderInfos ? implode(',',$orderInfos) : '';
                
                $deliveryInfos[$delivery_id] = $delivery;
                
                $deliveryInfos[$delivery_id]['order_bn'] = $orderInfos;
                $deliveryInfos[$delivery_id]['shop_bn'] = $shopInfos[$delivery['shop_id']]['shop_bn'];
                $deliveryInfos[$delivery_id]['shop_name'] = $shopInfos[$delivery['shop_id']]['name'];
                $deliveryInfos[$delivery_id]['branch_name'] = $branchInfos[$delivery['branch_id']]['name'];
                $deliveryInfos[$delivery_id]['branch_bn'] = $branchInfos[$delivery['branch_id']]['branch_bn'];
                $deliveryInfos[$delivery_id]['member_name'] = $memberInfos[$delivery['member_id']];
                $deliveryInfos[$delivery_id]['delivery_bn'] = $delivery['delivery_bn'];
                $deliveryInfos[$delivery_id]['ship_name'] = $delivery['ship_name'];
                $deliveryInfos[$delivery_id]['ship_area'] = $delivery['ship_province'].'-'.$delivery['ship_city'].'-'.$delivery['ship_district'];
                $deliveryInfos[$delivery_id]['ship_addr'] = $delivery['ship_addr'];
                $deliveryInfos[$delivery_id]['ship_zip'] = $delivery['ship_zip'];
                $deliveryInfos[$delivery_id]['ship_tel'] = $delivery['ship_tel'];
                $deliveryInfos[$delivery_id]['ship_mobile'] = $delivery['ship_mobile'];
                $deliveryInfos[$delivery_id]['ship_email'] = $delivery['ship_email'];
                $deliveryInfos[$delivery_id]['order_check_name'] = $opInfos[$delivery['op_id']];
                
                $sale_ordersInfos[$delivery_id] = $deliveryObj->getsale_price($orderIds);
                
                $deliveryInfos[$delivery_id]['mark_memo'] = $mark_memo;
                $deliveryInfos[$delivery_id]['custom_memo'] = $custom_memo;
                $deliveryInfos[$delivery_id]['cost_freight'] = $cost_freight;
                
                //京东包裹列表(云交易订单号)
                if($yjdf_branchs[$branch_id]){
                    $deliveryInfos[$delivery_id]['packages'] = $deliveryLib->getShipDlyPackages($delivery_id);
                }
                
                //签收状态
                if(in_array($delivery['logi_status'], array('3', '4'))){
                    $deliveryInfos[$delivery_id]['sign_status'] = 'true';
                    $deliveryInfos[$delivery_id]['sign_time'] = ($delivery['sign_time'] ? date('Y-m-d H:i:s', $delivery['sign_time']) : 0);
                }else{
                    $deliveryInfos[$delivery_id]['sign_status'] = 'false';
                }
                
                $deliveryInfos[$delivery_id]['logi_status'] = $logiStatus[$delivery['logi_status']];
                
                //初始化items
                $deliveryInfos[$delivery_id]['items'] = array();
            }
            
            if(count($deliveryIds) == 1){
                $_where_sql = " delivery_id =".$deliveryIds[0]."";
            }else{
                $_where_sql = " delivery_id in(".implode(',', $deliveryIds).")";
            }
            
            $delivery_items = $db->select("select bn,number,product_name,delivery_id,item_id,purchase_price from sdb_ome_delivery_items where ".$_where_sql."");
            foreach ($delivery_items as $k =>$delivery_item){
                $delivery_item['price'] = $sale_ordersInfos[$delivery_item['delivery_id']][$delivery_item['bn']];
                $delivery_item['bn']= $delivery_item['bn'];
                $delivery_item['product_name']= $delivery_item['product_name'];
                
                $deliveryInfos[$delivery_item['delivery_id']]['items'][$delivery_item['item_id']] = $delivery_item;
            }
            
            return array(
                'lists' => $deliveryInfos,
                'count' => $count['_c'],
            );
        }else{
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
    }
    private function getOrderId ($array){
        foreach ($array as $value) {
            $orderId[]=$value['order_id'];
        }
        return $orderId;
    }

    private function getBranch($branch_name)
    {
        $branchModel = app::get('ome')->model('branch');

        $branch = $branchModel->dump(array('name'=>trim($branch_name)),'branch_id');

        return $branch['branch_id'];
    }

    private function getShop($shop_name)
    {

        $shopModel = app::get('ome')->model('shop');

        $shop = $shopModel->dump(array('name'=>trim($shop_name)),'shop_id');

        return $shop['shop_id'];
    }

    private function getCrop($corp_name)
    {

        $corpModel = app::get('ome')->model('dly_corp');

        $corp = $corpModel->dump(array('name'=>trim($corp_name)),'corp_id');

        return $corp['corp_id'];
    }

     
    /**
     * 店铺信息
     * @param   
     * @return 
     * @access  private
     * @author sunjing@shopex.cn
     */
    private function getShopinfo()
    {
        $shopInfos = array();
        $shopModel = app::get('ome')->model('shop');
        $shop_arr = $shopModel->getList('shop_id,shop_bn,name', array(), 0, -1);
        foreach ($shop_arr as $k => $shop){
            $shopInfos[$shop['shop_id']] = $shop;
        }
        return $shopInfos;
    }
    
    /**
     * 仓库信息
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    private function getBranchinfo()
    {
        $branchInfos = array();
        $branchModel = app::get('ome')->model('branch');
        
        $branch_arr = $branchModel->getList('branch_id,name,branch_bn', array(), 0, -1);
        foreach ($branch_arr as $k => $branch){
            $branchInfos[$branch['branch_id']]['name'] = $branch['name'];
            $branchInfos[$branch['branch_id']]['branch_bn'] = $branch['branch_bn'];
        }
        
        return $branchInfos;
    }
    /**
     * 通过一个发货单号或一个发货单号数组，获取这些发货单号对应的订单号
     * 
     * @param string/array() $dly_ids
     * 
     * @return array(array())
     */
    private function getOrderIdByDeliveryIdForOpenApi($dly_ids){
        $dly_orderObj = app::get('ome')->model('delivery_order');
        $filter['delivery_id'] = $dly_ids;
        $data = $dly_orderObj->getList('order_id', $filter);
        foreach ($data as $item){
            $ids[] = $item;
        }
        return $ids;
    }
}