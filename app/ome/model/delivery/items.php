<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_delivery_items extends dbeav_model{

    /**
     * 创建大发货单对应的发货单详情
     *
     * @param bigint $parent_id
     * @param array() $items
     * 
     * @return boolean
     */
    function insertParentItemByItems($parent_id, $items, $branch_id){
        if (!is_array($items)) return false;
        $ids = implode(',', $items);
        $sql = "SELECT *,SUM(number) AS 'total' FROM sdb_ome_delivery_items WHERE delivery_id in ($ids) GROUP BY product_id";
        //echo $sql;
        $rows = $this->db->select($sql);
        if ($rows){
            //$dly_itemPosObj = $this->app->model('dly_items_pos');
            foreach ($rows as $item){
                $new_item['delivery_id']       = $parent_id;
                $new_item['shop_product_id']   = $item['shop_product_id'];
                $new_item['product_id']        = $item['product_id'];
                $new_item['bn']                = $item['bn'];
                $new_item['product_name']      = $item['product_name'];
                $new_item['number']            = $item['total'];
                $new_item['verify_num']        = 0;
                
                $this->save($new_item);
                
                /*$pos = $this->db->selectrow("SELECT bp.pos_id FROM sdb_ome_branch_pos AS bp 
                                                      LEFT JOIN sdb_ome_branch_product_pos AS bpp 
                                                            ON(bpp.pos_id=bp.pos_id) 
                                                      WHERE bp.branch_id=".intval($branch_id)." 
                                                            AND bpp.product_id=".$item['product_id']." 
                                                            AND default_pos='true'");
                if (empty($pos['pos_id'])) {
                    trigger_error($item['product_name'].":无默认货位", E_USER_ERROR);
                    return false;
                }
                $pos_id = $pos['pos_id'];
                $items_pos = array('item_id'=>$new_item['item_id'],'pos_id'=>$pos_id,'num'=>$item['total']);
                $dly_itemPosObj->save($items_pos);*/
                
                $new_item=NULL;
            }
            return true;
        }
        return false;
    }
        
    
    /**
     * 校验完成，对发货单对应详情进行更新
     *
     * @param bigint $dly_id
     * 
     * @return boolean
     */
    function verifyItemsByDeliveryId($dly_id){
        $items = $this->getList('item_id,number,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        foreach ($items as $item){
            $data['verify'] = 'true';
            $data['verify_num'] = $item['number'];
            
            if ($this->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
        }
        return true;
    }
    
    /**
     * 重置发货单详情
     *
     * @param bigint $dly_id
     * 
     * @return boolean
     */
    function resumeItemsByDeliveryId($dly_id){
        $items = $this->getList('item_id,number,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        foreach ($items as $item){
            if ($item['verify_num'] === 0 && $item['verify'] == 'false') continue;
            
            $data['verify'] = 'false';
            $data['verify_num'] = 0;
            
            $this->update($data, array('item_id'=>$item['item_id']));
            $data = null;
        }
        return true;
    }
    
    /*
     * 大单校验
     */
    function verifyItemsByDeliveryIdFromPost($dly_id)
    {
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        
        $items = $this->getList('item_id,number,product_id,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        
        foreach ($items as $item)
        {
            $barcode_val    = $basicMaterialBarcode->getBarcodeById($item['product_id']);
            
            $num = intval($_POST['number_'. $barcode_val]);
            $num = $num>$item['number']? $item['number'] : $num;
            $data['verify'] = 'false';
            $data['verify_num'] = $num;
            
            if ($this->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
            $_POST['number_'. $barcode_val] -= $num;
        }
        return true;
    }
    
    /**
     * 按基础物料搜索(支持批量多个)
     *
     * @param $filter
     * @return array|false
     */
    public function getDeliveryIdByPbn($filter)
    {
        //material_bn
        $material_bn = $filter['material_bn'];
        
        //check
        if(empty($material_bn)){
            return array(0=>array('delivery_id'=>-1));
        }
        
        //unset
        unset($filter['material_bn']);
        
        //@todo：千万别调用此方法,会循环调用导致死循环;
        //$deliveryObj = app::get('ome')->model('delivery');
        //$delivery_filter = $deliveryObj->_filter($filter);
        
        //filter
        $where = $this->simple_delivery_filter($filter);
        
        //product_bn
        if(is_array($material_bn)) {
            $where .= " AND i.bn IN('". implode("', '", $material_bn) ."')";
        }else{
            $where .= " AND i.bn='". addslashes($material_bn) ."'";
        }
        
        //count
        $sql = "SELECT count(*) AS _c FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $limit = 9000;
            $list = array();
            
            $sql = "SELECT i.delivery_id FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--)
            {
                $offset = $i * $limit;
                $rows = $this->db->selectlimit($sql, $limit, $offset);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            
            return $list;
        }
        
        //select
        $sql = "SELECT i.delivery_id FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
        $rows = $this->db->select($sql);
        
        return $rows;
    }
    
    /**
     * 按条形码搜索
     *
     * @param $product_barcode
     * @return array|false
     */
    public function getDeliveryIdByPbarcode($filter)
    {
        //barcode
        $barcode = $filter['product_barcode'];
        if(empty($barcode)){
            return array(0=>array('delivery_id'=>-1));
        }
        
        //unset
        unset($filter['product_barcode']);
        
        //获取条形码关联的货号
        $codeSql = "SELECT a.code, b.bm_id,b.material_bn FROM sdb_material_codebase AS a LEFT JOIN sdb_material_basic_material AS b ON a.bm_id=b.bm_id ";
        $codeSql .= " WHERE a.code='". addslashes($barcode) ."'";
        $materialInfo = $this->db->selectrow($codeSql);
        $material_bn = $materialInfo['material_bn'];
        if(empty($material_bn)){
            return array(0=>array('delivery_id'=>-1));
        }
        
        //@todo：千万别调用此方法,会循环调用导致死循环;
        //$deliveryObj = app::get('ome')->model('delivery');
        //$delivery_filter = $deliveryObj->_filter($filter);
        
        //where
        $where = $this->simple_delivery_filter($filter);
        
        //material_bn
        $where .= " AND i.bn='". addslashes($material_bn) ."'";
        
        //count
        $sql = "SELECT count(*) AS _c FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $limit = 9000;
            $list = array();
            
            $sql = "SELECT i.delivery_id FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--)
            {
                $offset = $i * $limit;
                $rows = $this->db->selectlimit($sql, $limit, $offset);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            
            return $list;
        }
        
        //select
        $sql = "SELECT i.delivery_id FROM sdb_ome_delivery_items AS i LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id ". $where;
        $rows = $this->db->select($sql);
        
        return $rows;
    }
    
    /**
     * 按单个货号搜索
     *
     * @param $filter
     * @return array|false
     */
    public function getDeliveryIdByFilter($filter)
    {
        $searchfilter = $filter;
        unset($searchfilter['_title_']);
        
        $product_bn = $filter['product_bn'];
        unset($searchfilter['product_bn']);
        
        //@todo：千万别调用此方法,会循环调用导致死循环;
        //$deliveryObj = app::get('ome')->model('delivery');
        //$delivery_filter = $deliveryObj->_filter($filter);
        
        //where
        $where = $this->simple_delivery_filter($searchfilter);
        
        //material_bn
        $where .= " AND i.bn='". addslashes($product_bn) ."'";
        
        //count
        $sql = "SELECT count(1) as _c FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id ". $where;
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $limit = 9000;
            $list = array();
            $sql = "SELECT d.delivery_id FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id ". $where;
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--)
            {
                $offset = $i * $limit;
                $rows = $this->db->selectlimit($sql, $limit, $offset);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        
        //list
        $sql = "SELECT d.delivery_id FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id ". $where;
        $rows = $this->db->select($sql);

        return $rows;
    }
    
    /**
     * 简洁版发货单filter
     *
     * @param $filter
     * @return void
     */
    public function simple_delivery_filter($filter)
    {
        //setting
        $where = " WHERE 1 ";
        $deliveryIds = array();
        
        //status
        if($filter['status']){
            if(is_array($filter['status'])){
                $where .= " AND d.status IN('". implode("', '", $filter['status']) ."')";
            }else{
                $where .= " AND d.status='". $filter['status'] ."'";
            }
        }
        
        //parent_id
        if($filter['parent_id']){
            $where .= " AND d.parent_id='". $filter['parent_id'] ."'";
        }
        
        //process
        if($filter['process']){
            if(is_array($filter['process'])){
                $where .= " AND d.process IN('". implode("', '", $filter['process']) ."')";
            }else{
                $where .= " AND d.process='". $filter['process'] ."'";
            }
        }
        
        //type
        if($filter['type']){
            if(is_array($filter['type'])){
                $where .= " AND d.type IN('". implode("', '", $filter['type']) ."')";
            }else{
                $where .= " AND d.type='". $filter['type'] ."'";
            }
        }
        
        //member_uname
        if (isset($filter['member_uname'])) {
            $memberObj = $this->app->model('members');
            $rows = $memberObj->getList('member_id', array('uname|has'=>$filter['member_uname']));
            $memberIds = array(0);
            foreach ($rows as $row)
            {
                $member_id = $row['member_id'];
                $memberIds[$member_id] = $member_id;
            }
            
            $where .= " AND d.member_id IN (". implode(',', $memberIds) .")";
            
            unset($filter['member_uname']);
        }
        
        //按订单号搜索
        if (isset($filter['order_bn'])) {
            // 多订单号查询
            if(strpos($filter['order_bn'], "\n") !== false){
                $filter['order_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['order_bn']))));
            }
            
            $orderObj = $this->app->model('orders');
            $rows = $orderObj->getList('order_id', array('order_bn'=>$filter['order_bn']));
            
            $orderIds = array(0);
            foreach ($rows as $row)
            {
                $order_id = $row['order_id'];
                $orderIds[$order_id] = $order_id;
            }
            
            $deliOrderObj = $this->app->model("delivery_order");
            $rows = $deliOrderObj->getList('delivery_id', array('order_id'=>$orderIds));
            
            //delivery_id
            $deliveryIds[0] = 0;
            if($rows){
                foreach($rows as $row)
                {
                    $temp_dly_id = $row['delivery_id'];
                    
                    $deliveryIds[$temp_dly_id] = $temp_dly_id;
                }
            }
            
            unset($filter['order_bn']);
        }
        
        //[单个货号]按货号查询
        if (isset($filter['product_bn'])){
            $where .= " AND d.bnsContent LIKE '%". $filter['product_bn'] ."%'";
            
            unset($filter['product_bn']);
        }
        
        //ship_tel
        if (isset($filter['ship_tel_mobile'])) {
            $where .= " AND (d.ship_tel='". $filter['ship_tel_mobile'] ."' OR d.ship_mobile='". $filter['ship_tel_mobile'] ."')";
            
            unset($filter['ship_tel_mobile']);
        }
        
        //branch_id
        if (isset($filter['ext_branch_id'])) {
            if (isset($filter['branch_id'])) {
                $where .= " AND d.branch_id=". intval($filter['branch_id']);
            } else {
                $where .= " AND d.branch_id=". intval($filter['ext_branch_id']);
            }
            
            unset($filter['ext_branch_id']);
        }
        
        //delivery_ids
        if($deliveryIds){
            $where .= " AND d.delivery_id IN (". implode(',', $deliveryIds) .")";
        }
        
        return $where;
    }
}
?>