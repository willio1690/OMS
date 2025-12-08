<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 在途库存.
 * @
 * @
 * @author sunjing@shopex.cn
 */
class ome_arrivestock
{
    
    
    
    function get_all_diff()
    {
        $db = kernel::database();
        $products = $db->select("SELECT * FROM sdb_ome_branch_product group by product_id");
        
        $total = count($products);
       
        $limit = 1000;
        $page = 0;
        $diff_list = array();
        for($page;$page < ($total / $limit);$page++){
            $sql = "select product_id,sum(arrive_store) as total_arrive_store from sdb_ome_branch_product group by product_id limit ".($page * $limit).",".$limit;
            
            $branch_products = $db->select($sql);
            $now_arrive_store = $product_ids =array();
            foreach ($branch_products as $product ) {
                $product_ids[] = $product['product_id'];
                $now_arrive_store[$product['product_id']] = $product['total_arrive_store'];
            }
            //计算当前ID有效在途库存
            $local_arrive_store = $this->get_local_arrive_store($product_ids);
            //计算在途库存流水之和
            $stream_arrive_store = $this->get_stream_arrive_store($product_ids);
            //比较两边值是否一致不一致即作为异常
            foreach ($product_ids as $product_id ) {
                $arrive_store=0;
                if ($local_arrive_store[$product_id]) {
                    foreach ($local_arrive_store[$product_id] as $lv ) {
                    
                        $arrive_store+=$lv;
                    }
                }
     
                if ($now_arrive_store[$product_id]!=$arrive_store || $arrive_store != $stream_arrive_store[$product_id]['num']) {
                    $diff_list[$product_id] = array('now_arrive_store'=>$now_arrive_store[$product_id],'local_arrive_store'=>$arrive_store,'stream_arrive_store'=>$stream_arrive_store[$product_id]['num'],'product_id'=>$product_id);
                }
                
            }
        }
        //$diff_list = array_unique($diff_list);
        
        return $diff_list;
    }

    
    /**
     * 获取当前本地在途库存
     * @param   
     * @return  
     * @access  public
     * @author 
     */

    function get_local_arrive_store($product_ids)
    {
        
        $db = kernel::database();
        $product_ids = (array)$product_ids;
        if(is_array($product_ids)) $product_ids = implode(',',$product_ids);
        //获取采购
        $po_sql = "SELECT i.product_id,sum(i.num-i.in_num) as _num,p.branch_id FROM sdb_purchase_po as p left join sdb_purchase_po_items as i ON p.po_id=i.po_id WHERE p.po_status='1' AND p.eo_status in('1','2') AND p.check_status in('2') AND i.product_id in (".$product_ids.") group by  p.branch_id,i.product_id";
       
        $arrive_store = array();
        $po = $db->select($po_sql);
        foreach ($po as $pv ) {
            if (isset($arrive_store[$pv['product_id']][$pv['branch_id']])) {
                $arrive_store[$pv['product_id']][$pv['branch_id']]+= $pv['_num'];
            }else{
                $arrive_store[$pv['product_id']][$pv['branch_id']] = $pv['_num'];
            }
            
        }
        //
        $iso_sql = "SELECT item.product_id,sum(item.nums) nums,iso.branch_id FROM sdb_taoguaniostockorder_iso  as iso LEFT JOIN sdb_taoguaniostockorder_iso_items as item ON iso.iso_id=item.iso_id WHERE iso.type_id in(4,50,70,200,400,800) and iso.check_status='2' AND iso.iso_status='1' AND item.product_id in (".$product_ids.") group by  iso.branch_id,item.product_id";
        $iso_list = $db->select($iso_sql);
        foreach ( $iso_list as $iso ) {
            if (isset($arrive_store[$iso['product_id']][$iso['branch_id']])) {
                $arrive_store[$iso['product_id']][$iso['branch_id']] += $iso['nums'];
            }else{
                $arrive_store[$iso['product_id']][$iso['branch_id']] = $iso['nums'];
            }
            
        }
        return $arrive_store;
    }

    /**
     * 获取_stream_arrive_store
     * @param mixed $product_ids ID
     * @return mixed 返回结果
     */
    public function get_stream_arrive_store($product_ids) {
        $arriveModel = app::get('material')->model('basic_material_stock_arrive');
        $sql = 'select bm_id, sum(num) num from sdb_material_basic_material_stock_arrive
                    where bm_id in ("'.implode('","', $product_ids).'")
                    group by bm_id';
        $rows = $arriveModel->db->select($sql);
        return array_column($rows, null, 'bm_id');
    }

    /**
     * repare_stream_arrive_store
     * @param mixed $product_id ID
     * @return mixed 返回值
     */
    public function repare_stream_arrive_store($product_id) {
        $data = $this->getStreamList($product_id);
        $arriveModel = app::get('material')->model('basic_material_stock_arrive');
        $sql = kernel::single('ome_func')->get_insert_sql($arriveModel, $data);
        $arriveModel->db->exec($sql);
    }

    /**
     * 获取StreamList
     * @param mixed $product_id ID
     * @return mixed 返回结果
     */
    public function getStreamList($product_id) {
        $db = kernel::database();

        $po_sqls = "SELECT i.product_id as bm_id,p.po_id as obj_id, p.po_bn as obj_bn, p.branch_id,(i.num-i.in_num) as num, 'purchase' as obj_type FROM sdb_purchase_po as p left join sdb_purchase_po_items as i ON p.po_id=i.po_id WHERE p.po_status='1' AND p.eo_status in('1','2') AND p.check_status in('2') AND i.product_id=".$product_id." AND  i.num>i.in_num";
        $po_lists = $db->select($po_sqls);
        //
        $iso_sql = "SELECT item.product_id as bm_id,iso.iso_id as obj_id, iso.iso_bn as obj_bn, iso.branch_id,item.nums as num,'iostockorder' as obj_type FROM sdb_taoguaniostockorder_iso  as iso LEFT JOIN sdb_taoguaniostockorder_iso_items as item ON iso.iso_id=item.iso_id WHERE iso.type_id in(4,50,70,200,400,800) and iso.check_status='2' AND iso.iso_status='1' AND item.product_id=".$product_id;

        $iso_list = $db->select($iso_sql);
        return array_merge($po_lists, $iso_list);
    }
}
