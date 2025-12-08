<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class archive_interface_orders {
 
    
    /**
     * 获取订单明细
     * @param   
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function getItemList($order_id,$sort=false)
    {
        $objectsObj = app::get('archive')->model('order_objects');
        if($sort){
            $order_objects = $objectsObj->getlist('*',array('order_id'=>$order_id));
            $order_items = array();
            foreach($order_objects as $k=>$v){
                $order_items[$v['obj_type']][$k] = $v;
                foreach($objectsObj->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE obj_id=".$v['obj_id']." AND item_type='product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
                foreach($objectsObj->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE obj_id=".$v['obj_id']." AND item_type<>'product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
            }
        }else{
            foreach($objectsObj->db->select("SELECT *,nums AS quantity FROM sdb_archive_order_items WHERE order_id=".$order_id." ") as $it){
                    $order_items[] = $it;
                }
        }
        
        
        
        return $order_items;
    }

    /*
     * 统计某订单货号生成退款单数
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    function Get_refund_count($order_id,$bn,$reship_id='')
    {
        $itemsObj = app::get('archive')->model('order_items');
        $sql = "SELECT sum(sendnum-return_num) as count FROM sdb_archive_order_items WHERE order_id='".$order_id."' AND bn='".$bn."'";
        $order=$itemsObj->db->selectrow($sql);
        if(empty($order['count'])){
            $order['count'] = 0;
        }
        
        $sql = "SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check!='5' AND r.order_id='".$order_id."' AND i.bn='".$bn."'";
        if($reship_id != ''){
            $sql .= ' AND r.reship_id!='.$reship_id;
        }
        
        $refund = $itemsObj->db->selectrow($sql);
        if(empty($refund['count'])){
            $refund['count'] = 0;
        }
        
        return $order['count'] - $refund['count'];
    }

    /*
    *  根据货号获取对应仓库和ID
    *
    * @param int $order_id ,varchar $bn
    *
    * * return array
    */
     function getBranchCodeByBnAndOd($bn,$orderid)
     {
         $oBranch=app::get('ome')->model('branch');

         $sqlstr = "SELECT s.branch_id,s.delivery_id FROM sdb_archive_delivery as s left join sdb_archive_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_archive_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$orderid' AND sdi.bn='$bn'";

        $branch=$oBranch->db->select($sqlstr);
        
        $branch_ids = array();
        $t_branch = $branch;
        foreach($t_branch as $k=>$v){
            if(!in_array($v['branch_id'],$branch_ids)){
            
                $branch[$k]['branch_name']=$oBranch->Get_name($v['branch_id']);
                $branch_ids[] = $v['branch_id'];
            }else{
                unset($branch[$k]);
            }
        }
        
        return $branch;
     }

     /*
  * 根据仓库ID，货号订单号获取发货单号以及对应收货相关信息
  * @param int $branch_id,int $order_id
  * return $array
  */
   function Get_delivery($branch_id,$bn,$order_id)
   {
        $delivObj = app::get('archive')->model('delivery');
        $sqlstr = "SELECT s.delivery_id,s.delivery_bn,s.ship_name,s.ship_area,s.ship_addr,sdi.bn,sum(sdi.number) as number FROM sdb_archive_delivery as s left join sdb_archive_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_archive_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$order_id' AND sdi.bn='$bn' AND s.branch_id='$branch_id' group by sdi.bn";

        $result=$delivObj->db->selectrow($sqlstr);

        $result['refund'] = $result['number']-$this->Get_refund_num($branch_id,$bn,$order_id);

        return $result;
   }
   /*
    *根据仓库，货号，订单号数量
    */
   function Get_refund_num($branch_id,$bn,$order_id)
   {
       
       $returnObj = app::get('ome')->model('return_product');
      
       $refund =  $returnObj->Get_refund_num($branch_id,$bn,$order_id);

       return $refund;
    }


    function getorderItems($order_id,$bn){
        $ordersObj =app::get('archive')->model('orders');
        $itemsql = "SELECT sendnum,bn,item_id, return_num FROM sdb_archive_order_items 
                                                WHERE order_id='".$order_id."' AND bn='$bn' AND sendnum != return_num";
        $orderItems=$ordersObj->db->select($itemsql);
        return $orderItems;
    }

    function save($data){
        $ordersObj =app::get('archive')->model('orders');
        if($data){
            $ordersObj->save($data);
        }
    
    }

    function getOrders($filter,$col="*"){
        $ordersObj =app::get('archive')->model('orders');
        $orders = $ordersObj->dump($filter,$col);
        return $orders;
    }

    function getOrder_list($filter,$col='*'){
        $ordersObj =app::get('archive')->model('orders');
        $order_list = $ordersObj->getList($col, $filter, 0, -1);
        return $order_list;
    }

    function update($data,$filter){
        $ordersObj =app::get('archive')->model('orders');
        if($data){
            $ordersObj->update($data,$filter);
        }
    
    }

    function getOrder_object($filter,$col='*'){
        $ordObj = app::get('archive')->model('order_objects');
        $order_object = $ordObj->getList($col,$filter);
        return $order_object;
    }
}

?>