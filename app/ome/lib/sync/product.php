<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sync_product{

    /**
     * 执行库存同步任务
     */
    function run_stock_sync(){
        $shop_info = kernel::database()->select("SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL");
        if($shop_info){
            //重置商品的冻结库存
            //$this->reset_freeze();
            foreach($shop_info as $v){
                if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;

                // 更新店铺的库存同步时间
                $last_store_sync_end = app::get('ome')->getConf('store_sync_end'.$shop_id);
                $store_sync_from = $last_store_sync_end?$last_store_sync_end:0;
                $store_sync_end = time();

                $cursor_id = 0;
                $params = array(
                    'store_sync_from'=>$store_sync_from,
                    'store_sync_end'=>$store_sync_end,
                    'shop_id' => $shop_id,
                    'node_type' => $node_type,
                );

                while(true) {
                    if(!$this->sync_stock($cursor_id,$params)){
                        break 1;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function add_stock_sync(){
        $shop_info = kernel::database()->select("SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL");
        if($shop_info){
            foreach($shop_info as $v){
                if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;
                if(!kernel::database()->selectrow("SELECT queue_id FROM sdb_base_queue WHERE worker='ome_sync_product.sync_stock' AND queue_title='".$queue_title."'")){
                    $last_store_sync_end = app::get('ome')->getConf('store_sync_end'.$shop_id);
                    $store_sync_from = $last_store_sync_end?$last_store_sync_end:0;
                    $store_sync_end = time();

                    app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                    app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);

                    $params = array(
                            'store_sync_from'=>$store_sync_from,
                            'store_sync_end'=>$store_sync_end,
                            'shop_id' => $shop_id,
                            'node_type' => $node_type,
                        );

                    $data = array(
                        'queue_title'=>$queue_title,
                        'start_time'=>time(),
                        'params'=>$params,
                        'cursor_id' => 0,
                        'worker'=>'ome_sync_product.sync_stock',
                    );
                    $queue_id = app::get('base')->model('queue')->insert($data);
                    app::get('base')->model('queue')->runtask($queue_id);

                    //$log = app::get('ome')->model('api_log');
                    //$log->write_log($log->gen_id(), '库存增加同步,队列ID：'. $queue_id . ' 店铺ID：'. $shop_id, 'ome_sync_product', 'add_stock_sync', '', '', 'response', 'success', var_export($params, true) . '<BR>'. var_export($data, true));
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function sync_stock(&$cursor_id,$params){

        if (!is_array($params)){
            $params = unserialize($params);
        }
        $limit = 20;
        $shop_id = $params['shop_id'];
        $node_type = $params['node_type'];
        $store_sync_from = $params['store_sync_from'];
        $store_sync_end = $params['store_sync_end'];
        $offset = $cursor_id;

        //if($offset==0) $this->reset_freeze();//重置商品的冻结库存

        //获取回写库存
        if ($stock_service = kernel::service('service.stock')){
            if(method_exists($stock_service,'calculate_stock')){
                $stocks = $stock_service->calculate_stock($shop_id, $store_sync_from, $store_sync_end, $offset, $limit);
            }
        }
        if ($stocks){
            if(is_array($stocks) && count($stocks)>0){
                foreach(kernel::servicelist('service.stock') as $object=>$instance){
                    if(method_exists($instance,'update_stock')){
                        $instance->update_stock($stocks,$shop_id,$node_type);
                    }
                }
            }
            if($offset==0){
                app::get('ome')->model('shop')->update(array('last_store_sync_time'=>$store_sync_end),array('shop_id'=>$shop_id));
                app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);
            }
            $offset = $offset + $limit;
            $cursor_id = $offset;
            return true;
        }else{
            return false;
        }
    }

    /**
     * 重置商品的冻结库存
     * redis库存高可用，废弃掉直接修改db库存、冻结的方法
     */
    function reset_freeze($product_id=0,$is_local = ''){
        if (empty($is_local) || $is_local != 'local') {
            return false;
            return false;
            return false;
        }

        $productObj = app::get('material')->model('basic_material_stock');//已不会调用该表和这段代码pdts，废弃 xiayuanjun
        $freezeObj = kernel::single('console_storefreeze');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $product_id = intval($product_id);
        $sale_product = $freezeObj->sale_freezeproduct($product_id);
        $p_freeze = array();
        if ($sale_product){
            foreach($sale_product as $pk=>$products){
                
                 if(isset($p_freeze[$pk])){
                    $p_freeze[$pk] += $products;
                }else{
                        $p_freeze[$pk] = $products;
                }
            }
        }

        $branch_freeze = array();
        $salestock_freeze = $freezeObj->sale_freezebranchproduct($product_id);
        $outstock_freeze = $freezeObj->branch_freezeproduct($product_id);

        if ($outstock_freeze){
            foreach ($outstock_freeze as $pk=>$freeze){
            
                foreach ($freeze as $fk=>$fr){
                    if (isset($p_freeze[$pk])){
                        $p_freeze[$pk] += $fr;
                    }else{
                        $p_freeze[$pk]= $fr;
                    }
                    if ($fr){
                        if (isset($branch_freeze[$pk][$fk])){
                            $branch_freeze[$pk][$fk]+=$fr;
                        }else{
                            $branch_freeze[$pk][$fk]=$fr;
                        }
                    }
                }
            
            }
        }
        
        if ($salestock_freeze){
            foreach($salestock_freeze as $sk=>$salefreeze){
                foreach ($salefreeze as $ak=>$afreeze ){
                    if ($afreeze){
                        if(isset($branch_freeze[$sk][$ak])){
                            $branch_freeze[$sk][$ak]+=$afreeze;
                        }else{
                            $branch_freeze[$sk][$ak] = $afreeze;
                        }
                        
                    }
                }
            }
        }
        #update
        $get_order_sql = "UPDATE sdb_material_basic_material_stock SET store_freeze=0";
        if($product_id>0) $get_order_sql .= " WHERE bm_id=$product_id ";
        kernel::database()->exec($get_order_sql);

        foreach($p_freeze as $productId=>$store_freeze){            
            $lastinfo = kernel::database()->selectrow('select s.store_freeze,m.material_bn AS bn from sdb_material_basic_material_stock as s left join sdb_material_basic_material as m on s.bm_id=m.bm_id where s.bm_id='.intval($productId));
            
            //根据基础物料ID获取对应的冻结库存
            $lastinfo['store_freeze']    = $basicMStockFreezeLib->getMaterialStockFreeze($productId);
            
            $sql = "UPDATE sdb_material_basic_material_stock SET store_freeze=".$store_freeze ." WHERE bm_id=".$productId;
            kernel::database()->exec($sql);

            //danny_freeze_stock_log
            //$currentinfo = kernel::database()->selectrow('select s.store_freeze,m.material_bn  as bn from sdb_material_basic_material_stock as s left join sdb_material_basic_material as m on s.bm_id=m.bm_id where s.bm_id ='.intval($productId));
            
            //根据基础物料ID获取对应的冻结库存
            $currentinfo = array();
            $currentinfo['store_freeze']    = $basicMStockFreezeLib->getMaterialStockFreeze($productId);
            
            //logs
            $log = array(
                    'log_type'=>'order',
                    'mark_no'=>uniqid(),
                    'oper_time'=>time(),
                    'product_id'=>$productId,
                    'goods_id'=>0,
                    'bn'=>$lastinfo['bn'],
                    'stock_action_type'=>'覆盖',
                    'last_num'=>$lastinfo['store_freeze'],
                    'change_num'=>$store_freeze,
                    'current_num'=>$currentinfo['store_freeze'],
            );
            kernel::single('ome_freeze_stock_log')->changeLog($log);
            
        }

       $this->reset_branch_freeze($product_id,$branch_freeze);
    }

    /**
     * 重置仓库的冻结库存
     */
    function reset_branch_freeze($product_id,$branch_freeze=array())
    {
        $libBranchProduct    = kernel::single('ome_branch_product');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        // reset branch store_freeze 2011.12.28
        $sql = "update sdb_ome_branch_product set store_freeze=0";
        if($product_id>0) $sql .= " where product_id=$product_id ";
        kernel::database()->exec($sql);


        foreach($branch_freeze as $bk=>$bv){
            foreach ($bv as $fk=>$freeze){
            $total_num = intval($freeze);
            $branch_id = $fk;
            $productId = $bk;
            $libBranchProduct->chg_product_store_freeze($branch_id,$productId,$total_num,'=');
            
                //danny_freeze_stock_log                
                $product_info = kernel::database()->selectrow('select m.material_bn  as bn from sdb_material_basic_material as m  where m.bm_id='.$productId);
                //$lastinfo = kernel::database()->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$productId.' AND branch_id = '.$branch_id);
                
                //根据基础物料ID获取对应的冻结库存
                $lastinfo = array();
                $lastinfo['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($productId, $branch_id);
                
                $branchinfo = kernel::database()->selectrow('select name from sdb_ome_branch where branch_id = '.$branch_id);

                $sql = "UPDATE sdb_ome_branch_product SET store_freeze=".$total_num." WHERE product_id=".$productId." AND branch_id = ".$branch_id;
                kernel::database()->exec($sql);

                //danny_freeze_stock_log
                //$currentinfo = kernel::database()->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$productId.' AND branch_id = '.$branch_id);
                
                //根据基础物料ID获取对应的冻结库存
                $currentinfo = array();
                $currentinfo['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($productId, $branch_id);
                
                //logs
                $log = array(
                        'log_type'=>'delivery',
                        'mark_no'=>uniqid(),
                        'oper_time'=>time(),
                        'product_id'=>$productId,
                        'goods_id'=>0,
                        'bn'=>$product_info['bn'],
                        'branch_id'=>$branch_id,
                        'branch_name'=>$branchinfo['name'],
                        'stock_action_type'=>'覆盖',
                        'last_num'=>$lastinfo['store_freeze'],
                        'change_num'=>$total_num,
                        'current_num'=>$currentinfo['store_freeze'],
                );
                kernel::single('ome_freeze_stock_log')->changeLog($log);


            unset($v);
            }
        }

    }

    /**
     * 执行店铺所有商品的库存同步
     */
    function shop_stock_sync($shop_id=''){
        $sql = "SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL";
        if(!empty($shop_id)){
            $where = " and shop_id='".$shop_id."'";
            $sql .= $where;
        }
        $shop_info = kernel::database()->select($sql);
        if($shop_info){
            foreach($shop_info as $v){
                if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;

                $cursor_id = 0;
                $params = array(
                    'store_sync_from'=>time(),
                    'store_sync_end'=>time(),
                    'shop_id' => $shop_id,
                    'node_type' => $node_type,
                );

                while(true) {
                    if(!$this->shop_sync_stock($cursor_id,$params)){
                        break 1;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function shop_sync_stock(&$cursor_id,$params){
        if (!is_array($params)){
            $params = unserialize($params);
        }
        $limit = 20;
        $shop_id = $params['shop_id'];
        $node_type = $params['node_type'];
        $store_sync_from = $params['store_sync_from'];
        $store_sync_end = $params['store_sync_end'];
        $offset = $cursor_id;

        //if($offset==0) $this->reset_freeze();//重置商品的冻结库存

        //获取回写库存
        if ($stock_service = kernel::service('service.stock')){
            if(method_exists($stock_service,'shop_calculate_stock')){
                $stocks = $stock_service->shop_calculate_stock($shop_id, $store_sync_from, $store_sync_end, $offset, $limit);
            }
        }

        if ($stocks){
            if(is_array($stocks) && count($stocks)>0){
                foreach(kernel::servicelist('service.stock') as $object=>$instance){
                    if(method_exists($instance,'update_stock')){
                        $instance->update_stock($stocks,$shop_id,$node_type);
                    }
                }
            }

            // 更新店铺的库存同步时间
            if($offset==0){
                app::get('ome')->model('shop')->update(array('last_store_sync_time'=>$store_sync_end),array('shop_id'=>$shop_id));
                app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);
            }
            $offset = $offset + $limit;
            $cursor_id = $offset;
            return true;
        }else{
            return false;
        }
    }
}