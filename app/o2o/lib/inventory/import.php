<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_inventory_import
{

    /**
     * run
     * @param mixed $cursor_id ID
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function run(&$cursor_id, $params)
    {
        /*
         * 新增明细表中的数据
         * csv中填写的是 accounts_num线上账面数 和 accounts_share_num共享账面数
         * store线上实际数  share_store共享实际数 从sdb_o2o_product_store表中拿
         * 获取inventory_items明细表中 盈亏公式：short_over线上盘盈亏 = accounts_num - store ; share_short_over共享盘盈亏 = accounts_share_num - share_store ;
         */

        $bpModel = app::get('ome')->model('branch_product');
        $mdlO2oInventoryItems = app::get('o2o')->model('inventory_items');
        $inventory_id = $params["sdfdata"]["inv_id"];
        //统一获 store线上实际数  share_store共享实际数
        $bm_ids = array();
        foreach ($params["sdfdata"]["products"] as $var_product) {
            $bm_ids[] = $var_product["bm_id"];
        }
        $rs_product_store = $bpModel->getList("product_id as bm_id,store", array("branch_id" => $params["sdfdata"]["branch_id"], "product_id|in" => $bm_ids));

        $mode = $params["sdfdata"]['mode'];


    
        $rl_bm_id_store   = array();
        //当前门店仓关联的物料首次盘点 没有此记录 后续insert一条store=0 share_store=0的记录
        if (!empty($rs_product_store)) {
            foreach ($rs_product_store as $var_product_store) {
                $rl_bm_id_store[$var_product_store["bm_id"]] = array(
                    "store"       => $var_product_store["store"],
                    
                );
            }
        }
        //新增明细表中的数据
        foreach ($params["sdfdata"]["products"] as $var_p) {
            
            $bm_id = $var_p['bm_id'];
            $actual_num       = intval($var_p["actual_num"]);
           
            if (isset($rl_bm_id_store[$var_p["bm_id"]])) {
                $store       = intval($rl_bm_id_store[$var_p["bm_id"]]["store"]);
                
            } else {
               
                $store       = 0;
               
            }
            if($mode== '2'){
                $short_over       = $var_p['diff_stores'];

            }else{
                $short_over       = $actual_num - $store;
            }
            
            $items_detail = $mdlO2oInventoryItems->dump(array('inventory_id' => $inventory_id, 'bm_id' =>$bm_id),'item_id');

            if ($items_detail){
                $item_filter = array('inventory_id'=>$inventory_id,'item_id'=>$items_detail['item_id']);
                $item_data = array(

                    'accounts_num'       => $store,
                   
                    'actual_num'         => $actual_num,
                  
                    'short_over'         => $short_over,
                
                );
             
                $mdlO2oInventoryItems->update($item_data,$item_filter);
            }else{
                $insert_arr       = array(
                    "inventory_id"       => $params["sdfdata"]["inv_id"],
                    "bm_id"              => $var_p["bm_id"],
                    "accounts_num"       => $store,
                    "actual_num"         => $actual_num,
                   
                    "short_over"         => $short_over,
                    'material_bn'        =>  $var_p['material_bn'],
                    'material_name'      =>  $var_p['material_name'],
                    'price'              =>  $var_p['price'],
                    'amount'             =>  $var_p['amount'],
                    'pos_accounts_num'   =>  $var_p['pos_accounts_num'],   
                );

                $mdlO2oInventoryItems->insert($insert_arr);
            }
            
        }

        return false;
    }
}
