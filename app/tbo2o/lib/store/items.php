<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 淘宝门店关联宝贝处理Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_store_items
{
    function __construct(){
        $this->_storeItemObj    = app::get('tbo2o')->model('store_items');
    }
    
    /**
     * 新建商品
     * 
     * @param $bm_id 基础物料ID
     * @param $store_id 门店仓库ID
     * @return void
     **/
    public function create($bm_id, $branch_id, &$errormsg)
    {
        //[基础物料]门店供货关系
        $branchProductLib    = app::get('o2o')->model('branch_product');
        $productRow          = $branchProductLib->dump(array('branch_id'=>$branch_id, 'bm_id'=>$bm_id), 'id, status');
        if(empty($productRow))
        {
            $errormsg    = '门店供货记录不存在';
            return false;
        }
        if($productRow['status'] != 1)
        {
            $errormsg    = '销售状态为停售';
            return false;
        }
        
        //根据门店仓库ID找到对应门店store_id
        $branchObj    = app::get('ome')->model('branch');
        $branchRow    = $branchObj->dump(array('branch_id'=>$branch_id), 'branch_bn');
        
        /**
        $storeObj     = app::get('o2o')->model('store');
        $o2oStoreRow  = $storeObj->dump(array('store_bn'=>$branchRow['branch_bn']), 'store_id, store_bn, name');
        $store_id     = $o2oStoreRow['store_id'];
        
        $storeObj    = app::get('tbo2o')->model('store');
        $storeRow    = $storeObj->dump(array('local_store_id'=>$store_id), 'store_id, status, outer_store_id, sync');
        **/
        
        //[淘宝门店]关联关系
        $storeObj    = app::get('tbo2o')->model('store');
        $storeRow    = $storeObj->dump(array('store_bn'=>$branchRow['branch_bn']), 'store_id, store_bn, store_name, status, outer_store_id, sync');
        if(empty($storeRow))
        {
            $errormsg    = '淘宝门店记录不存在';
            return false;
        }
        if($storeRow['status'] != 'normal')
        {
            $errormsg    = '淘宝门店未营业';
            return false;
        }
        if(empty($storeRow['outer_store_id']))
        {
            $errormsg    = '淘宝门店不是同步状态';
            return false;
        }
        
        //根据基础物料查找对应销售物料(过滤促销、赠品类型)
        $sql    = "SELECT a.sm_id, a.sales_material_bn FROM sdb_material_sales_material AS a 
                   LEFT JOIN sdb_material_sales_basic_material AS b ON a.sm_id=b.sm_id 
                   WHERE b.bm_id=". $bm_id ." AND a.disabled='false' AND a.sales_material_type=1";
        $salesList    = $this->_storeItemObj->db->select($sql);
        if(empty($salesList))
        {
            $errormsg    = '未找到关联的销售物料';
            return false;
        }
        
        $sale_bn_list    = array();
        foreach ($salesList as $key => $val)
        {
            $sale_bn_list[]    = $val['sales_material_bn'];
        }
        
        //根据销售物料查找淘宝前端店铺的货品
        $shopSkuObj     = app::get('tbo2o')->model('shop_skus');
        $skuList        = $shopSkuObj->getList('shop_iid', array('shop_product_bn'=>$sale_bn_list));
        if(empty($skuList))
        {
            $errormsg    = '未找到淘宝前端店铺的货品';
            return false;
        }
        
        $iid_list    = array();
        foreach ($skuList as $key => $val)
        {
            $iid_list[]    = $val['shop_iid'];
        }
        
        //获取淘宝前端店铺的商品
        $shopItemObj    = app::get('tbo2o')->model('shop_items');;
        $itemList       = $shopItemObj->getList('id, iid, bn, title', array('iid'=>$iid_list));
        
        //创建门店关联前端店铺的商品
        $sql_val    = array();
        $dateline   = time();
        foreach ($itemList as $key => $val)
        {
            $row    = $this->_storeItemObj->dump(array('id'=>$val['id']), 'item_bn');
            if($row)
            {
                continue;
            }
            
            $str    = "('". $val['id'] ."', '". $val['iid'] ."', '". $val['bn'] ."', '". $val['title'] ."'";
            $str    .= ", '". $storeRow['store_id'] ."', '". $storeRow['store_bn'] ."', '". $storeRow['store_name'] ."'";
            $str    .= ", 0, 0)";
            
            $sql_val[]    = $str;
        }
        
        if($sql_val)
        {
            $sql       = 'INSERT INTO sdb_tbo2o_store_items(id, item_iid, item_bn, item_name, store_id, store_bn, store_name, is_bind, bind_time) VALUES';
            $sqlInsert = $sql.implode(',', $sql_val);
            
            $this->_storeItemObj->db->exec($sqlInsert);
        }
        
        return true;
    }
    
    /**
     * 批量插入
     *
     * @param $bm_ids Array 多个基础物料ID
     * @param $brach_id intval 门店仓库ID
     * @return boolean
     **/
    public function batchCreate($bm_ids, $branch_id, &$errormsg)
    {
        if(empty($bm_ids) || empty($branch_id))
        {
            return false;
        }
        
        foreach($bm_ids as $key => $bm_id)
        {
            $this->create($bm_id, $branch_id, $errormsg);
        }
        
        return true;
    }
}