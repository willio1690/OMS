<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_taog_instance implements tgstockcost_interface_cost
{
    /*创建期初数据队列*/
    public function create_queue()
    {
        if(app::get("ome")->getConf("tgstockcost.cost") == "4"){
            $fifo = app::get("tgstockcost")->model("fifo");

            $fifo->db->exec('truncate table ' . $fifo->table_name(1));
        }

        $branch_mdl = app::get("ome")->model("branch");
        $branch_data = $branch_mdl->getList("branch_id,name");
        $oQueue = app::get("base")->model("queue");
        foreach((array)$branch_data as $k=>$val)
        {
            $title=$val['name']." 仓库期初数据";
            $params['branch_id'] = $val['branch_id'];
            $queueData = array(
                'queue_title'=>$title,
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$params,
                ),
                'worker'=>'tgstockcost_taog_instance.run_tg_queue',
            );
            $oQueue->save($queueData);
        }
    }

    function run_tg_queue(&$cursor_id,$params,&$errmsg)
    {
        return $this->run_queue($params);
    }
    /*执行队列*/
    function run_queue($params)
    {
        $branch_id = $params['sdfdata']['branch_id'];
        $branch_product_mdl = app::get("ome")->model("branch_product");
        $fifo = app::get("tgstockcost")->model("fifo");

        //$fifo->db->exec('truncate table ' . $fifo->table_name(1));

        $dailystock = app::get("ome")->model("dailystock");

        $sql    = "SELECT obp.product_id,obp.store, bps.material_bn AS bn, ext.cost
                   FROM sdb_ome_branch_product AS obp
                   LEFT JOIN sdb_material_basic_material AS bps ON obp.product_id=bps.bm_id
                   LEFT JOIN sdb_material_basic_material_ext AS ext ON bps.bm_id=ext.bm_id
                   WHERE obp.branch_id=".intval($branch_id);

        $aData = $branch_product_mdl->db->select($sql);

        foreach(($aData) as $k=>$val)
        {
            $branch_product_mdl->update(array("unit_cost"=>$val['cost'],"inventory_cost"=>$val['store']*$val['cost']),array("product_id"=>$val["product_id"],"branch_id"=>$branch_id));

            //安装后的当天的期初数据
            // if($install_time = app::get("ome")->getConf("tgstockcost_install_time")){
            //     $dailystock_data = array();
            //     $dailystock_data['stock_date'] = date('Y-m-d',$install_time);
            //     $dailystock_data['branch_id'] = $branch_id;
            //     $dailystock_data['product_id'] = $val['product_id'];
            //     $dailystock_data['product_bn'] = $val['bn'];
            //     $dailystock_data['stock_num'] = $val['store'];
            //     $dailystock_data['unit_cost'] = $val['cost'];
            //     $dailystock_data['inventory_cost'] = $val['store']*$val['cost'];
            //     $dailystock_data['is_change'] = 1;
            //     $dailystock->save($dailystock_data);
            // }
            if(app::get("ome")->getConf("tgstockcost.cost") == "4" ){//存货计价法为先进先出法
                $save_data = array();
                $save_data['product_id']  = $val['product_id'];
                $save_data['branch_id']  = $branch_id;
                $save_data['product_bn']  = $val['bn'];
                $save_data['current_num']  = $val['store'];
                $save_data['in_num']  = $val['store'];
                $save_data['out_num']  = 0;
                $save_data['current_unit_cost']  = $val['cost'];
                $save_data['current_inventory_cost']  = $val['store']*$val['cost'];
                $save_data['is_sart']  = 1;

                $fifo->save($save_data);
            }
        }

    }

    /*销售出库 更新销售单成本金额和成本单价等字段*/
    function set_sales_iostock_cost($io,$data)
    {
        if($io!=0)return false;
    
        $deliveryMdl = app::get('ome')->model('delivery');
        $salesMdl    = app::get("ome")->model("sales");
        $sales_items = app::get("ome")->model("sales_items");
    
        //查询当前订单下的所有发货单id
        $order_ids    = array_unique(array_column($data, 'order_id'));
        $delivery_ids = $deliveryMdl->getDeliverIdByOrderId($order_ids);
        //sale_id做更新条件
        $sales_detail = $salesMdl->getList('order_id,sale_id',array('order_id' => $order_ids));
        $saleId       = array_column($sales_detail,null,'order_id');
        if(!$saleId)return false;
    
        if (count($delivery_ids) >= 1) {
            $in_delivery_id = implode(',', $delivery_ids);
            $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();
            $field = 'io.unit_cost,io.inventory_cost';
            if ($costSetting['branch_cost']['value'] == '2') {
                $field = 'io.entity_unit_cost as unit_cost,io.entity_inventory_unit_cost as inventory_cost';
            }
            $sql = 'select did.order_item_id, did.product_id,' . $field. ',did.item_type,io.iostock_id,did.order_obj_id,oo.bn,did.number,oo.order_id
                    from sdb_ome_delivery_items_detail did left join sdb_ome_iostock io
                    on (did.item_detail_id = io.original_item_id and did.delivery_id = io.original_id)
                    left join sdb_ome_order_objects oo on did.order_obj_id = oo.obj_id
                    where did.delivery_id in(' . $in_delivery_id . ') AND io.type_id=3';
        
            unset($order_ids, $in_delivery_id, $data);
            $item_detail = $sales_items->db->select($sql);
            
            $temp_data = $pkg_temp_data = $pkg_obj_cost = array();
            foreach ($item_detail as $key => $val) {
                $order_item_id             = $val['order_item_id'];
                $temp_data[$order_item_id] = floatval($temp_data[$order_item_id]) + floatval($val['inventory_cost']);
            }
            foreach ($item_detail as $key => $val) {
                $order_item_id         = $val['order_item_id'];
                $val['inventory_cost'] = $temp_data[$order_item_id];//合并拆分后的库存成本
                $item_detail[$key]     = $val;
            
                //记录PKG捆绑商品中包含商品的库存成本&&平均成本
//                if ($val['item_type'] == 'pkg' || $val['item_type'] == 'pko' || $val['item_type'] == 'lkb') {
//                    $pkg_temp_data[$val['order_obj_id']][$val['order_item_id']] = array('unit_cost'      => $val['unit_cost'], 'inventory_cost' => $val['inventory_cost']);
//                }
            }

//            //合计PKG捆绑商品中包含商品item_id的库存成本
//            foreach ($pkg_temp_data as $obj_id => $items) {
//                foreach ($items as $item_id => $item) {
//                    $pkg_obj_cost[$obj_id]['unit_cost']      += $item['unit_cost'];
//                    $pkg_obj_cost[$obj_id]['inventory_cost'] += $item['inventory_cost'];
//                }
//            }
//            foreach ($item_detail as $key => $val) {
//                $order_obj_id = $val['order_obj_id'];
//                if ($pkg_obj_cost[$order_obj_id]) {
//                    $item_detail[$key]['unit_cost']      = $pkg_obj_cost[$order_obj_id]['unit_cost'];
//                    $item_detail[$key]['inventory_cost'] = $pkg_obj_cost[$order_obj_id]['inventory_cost'];
//                }
//            }
            unset($temp_data);
        }
        $new = array();
        foreach ($item_detail as $key => $value) {
            if (isset($new[$value['product_id']])) {
                $new[$value['order_item_id']]['number']         += $value['number'];
            } else {
                $new[$value['order_item_id']]['unit_cost']      = $value['unit_cost'];
                $new[$value['order_item_id']]['inventory_cost'] = $value['inventory_cost'];
                $new[$value['order_item_id']]['item_type']      = $value['item_type'];
                $new[$value['order_item_id']]['number']         = $value['number'];
                $new[$value['order_item_id']]['product_id']     = $value['product_id'];
                $new[$value['order_item_id']]['bn']             = $value['bn'];
                $new[$value['order_item_id']]['iostock_id']     = $value['iostock_id'];
                $new[$value['order_item_id']]['order_id']       = $value['order_id'];
            }
        }
    
        foreach ($new as $k => $v) {
            if ($v['item_type'] == 'product' || $v['item_type'] == 'gift' || $v['item_type'] == 'adjunct') {
                $cost_price  = $v['unit_cost'];
                $cost_amount = $v['inventory_cost'];
                $sales_items->db->exec("UPDATE sdb_ome_sales_items set cost=$cost_price,cost_amount=$cost_amount,gross_sales=sales_amount-$cost_amount,gross_sales_rate=IF(sales_amount > 0 ,ROUND(gross_sales/sales_amount,4),0)*100 where sales_material_bn ='" . $v['bn'] . "' and product_id = " . $v['product_id'] . " and sale_id='" . $saleId[$v['order_id']]['sale_id'] . "' and order_item_id=" . $k);
            } elseif ($v['item_type'] == 'pkg' || $v['item_type'] == 'pko' || $v['item_type'] == 'lkb') {//更新捆绑商品销售明细中商品成本
                $pkg[$v['iostock_id']]['iostock_id']     = $v['iostock_id'];
                $pkg[$v['iostock_id']]['bn']             = $v['bn']; //销售物料bn
                $pkg[$v['iostock_id']]['unit_cost']      = $v['unit_cost'];
                $pkg[$v['iostock_id']]['inventory_cost'] = $v['inventory_cost'];
                $pkg[$v['iostock_id']]['number']         = $v['number'];
                $pkg[$v['iostock_id']]['product_id']     = $v['product_id'];
                $pkg[$v['iostock_id']]['order_id']       = $v['order_id'];
            }
        }
        unset($item_detail,$new);

        if ($pkg) {
            foreach ($pkg as $k => $v) {
                $cost_price  = $pkg[$k]['unit_cost'];
                $cost_amount = $pkg[$k]['inventory_cost'];
                $sales_items->db->exec("UPDATE sdb_ome_sales_items set cost=$cost_price,cost_amount=$cost_amount,gross_sales=sales_amount-$cost_amount,gross_sales_rate=IF(sales_amount > 0 ,ROUND(gross_sales/sales_amount,4),0)*100 where obj_type IN('pkg','pko','lkb') and sales_material_bn = '" . $pkg[$k]['bn'] . "' and product_id = " . $v['product_id'] . " and sale_id='" . $saleId[$v['order_id']]['sale_id'] . "' and nums=" . $v['number']);
            }
            unset($pkg);
        }
    }

    /*各种出入库操作实现*/
    function iostock_set($io,$data)
    {
        $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();

        $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");
        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");

        if(!$setting_stockcost_cost) return ;

        $iostock = app::get("ome")->model("iostock");
        if($io==1){//入库
            foreach((array)$data as $data_k=>$data_v)
            {
                $data_v['product_id'] = $this->get_product_id($data_v['bn']);
                if ($data_v['type_id']  == 50){//残损入库时 成本计算复用原始类型
                    $orig_type_id = $data_v['orig_type_id'];
                    if ($orig_type_id) $data_v['type_id'] = $orig_type_id;
                    $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
                    //总仓单位信息与平均成本
                    $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();
                    if ($costSetting['branch_cost']['value'] == '2') {
                        $unit_cost = $data_v['iostock_price'];
                    }
                }
                elseif($data_v['type_id'] == 1 || $data_v['type_id']  == 70 || $data_v['type_id']  == 200 || $data_v['type_id']  == 400){ //采购入库/赠品入库/直接入库/样品入库
                    //调账成本
                    if(in_array($data_v['bill_type'],array('branchadjust','storeadjust','branchadjust_init','storeadjust_init'))){
                        $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
                    }elseif(in_array($data_v['bill_type'],['workorder'])){
                        $unit_cost = $this->getMaUnitCost($data_v);
                    }else{
                        $unit_cost = $data_v['iostock_price'];
                    }
                    
                }
                elseif( $data_v['type_id'] == 30 || $data_v['type_id']  == 31  || $data_v['type_id']  == 32 ){ //退货入库/换货入库/拒收退货入库

                    // 默认取销售出库成本
                    $unit_cost = $this->get_sale_unit_cost($data_v);
                    
                    if( $unit_cost == 0 && $setting_stockcost_cost != '1' ){//如果 销售单位成本为0 就取商品成本(不记成本除外)
                        $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
                    }
                }
                elseif($data_v['type_id']  == 60 || $data_v['type_id']  == 500 ){ //盘盈/期初
                    $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);

                    if( $unit_cost == 0 && $setting_stockcost_cost != '1' ){
                        $unit_cost = $this->get_product_cost($data_v['product_id']);
                    }
                }elseif($data_v['type_id']  == 4){//调拨入库
                    $unit_cost = $data_v['iostock_price'];//取该商品调拨出库的成本价
                }else{                                                            //其他情况 如：调拨入库/残损入库
                    $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
                }
                $this->update_iostock($data_v,$unit_cost,'+'); //更新出入库流水成本等字段和仓库货品表的库存成本和单位成本字段
                if($setting_stockcost_cost == '4') //先进先出 插入入库FIFO表
                {
                    $this->insert_fifo($data_v,$unit_cost);
                }
            }
        }
        if($io==0){//出库
            //出库时 只要是先进先出法 出库单位成本都等于先进先出表的平均出库成本
            foreach((array)$data as $data_k=>$data_v)
            {
                $data_v['product_id'] = $this->get_product_id($data_v['bn']);
                if($setting_stockcost_cost == '4') $fifo_out_data = $this->fifo_stock($data_v);
                if($data_v['type_id'] == 3){ //销售出库
                    if($setting_stockcost_cost == '2'){ //固定成本法
                        $unit_cost = $this->get_product_cost($data_v['product_id']);
                    }
                    elseif($setting_stockcost_cost == '3'){ //平均成本法
                        $unit_cost = $this->get_product_unit_cost($data_v['product_id'],$data_v['branch_id']);
                    }
                    elseif($setting_stockcost_cost == '4') //先进先出
                    {
                        $unit_cost = $fifo_out_data['unit_cost'];
                    }
                    //修改商品销售发货明细单
                }elseif($data_v['type_id']  == 6 ){ //盘亏
                    $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);

                    if( $unit_cost == 0 && $setting_stockcost_cost != '1' ){
                        $unit_cost = $this->get_product_cost($data_v['product_id']);
                    }
                    unset($fifo_out_data['inventory_cost_total']);
                    //if($setting_stockcost_cost == '4') $unit_cost = $fifo_out_data['unit_cost'];
                }elseif($data_v['type_id']  == 10 ){ //采购退货
                    $unit_cost = $data_v['iostock_price'];
                }else{                                                            //其他情况 如：残损出库/调拨出库/赠品出库/直接出库/样品出库
                    $unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
                    if($setting_stockcost_cost == '4') $unit_cost = $fifo_out_data['unit_cost'];
                }
                $this->update_iostock($data_v,$unit_cost,'-',$fifo_out_data['inventory_cost_total']); //更新出入库流水成本等字段和仓库货品表的库存成本和单位成本字段
            }
        }
    }
    function getMaUnitCost($data_v) {
        $unit_cost = 0;
        $arrProductId = $data_v['product_id'];
        $bmci         = app::get('material')->model('basic_material_combination_items')->getList('*', ['pbm_id' => $arrProductId]);
        foreach ($bmci as $v) {
            $unit_cost += $this->get_unit_cost($v['bm_id'],$v['material_bn'],$data_v['branch_id']) * $v['material_num'];
        }
        return $unit_cost;
    }
    /*取货品单位成本*/
    function get_unit_cost($product_id,$product_bn,$branch_id)
    {
        $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");
        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");

        if($setting_stockcost_get_value_type == '1'){ //取货品的固定成本
            $unit_cost = $this->get_product_cost($product_id);
        }
        elseif($setting_stockcost_get_value_type == '2'){ //取货品的单位平均成本
            $unit_cost = $this->get_product_unit_cost($product_id,$branch_id);
        }
        elseif($setting_stockcost_get_value_type == '3'){//取货品的最近一次出入库成本  to 如果在该仓库下没有出入库记录？
            $unit_cost = $this->get_last_product_unit_cost($product_bn,$branch_id,$product_id);

        }
        elseif($setting_stockcost_get_value_type == '4'){//取0
            $unit_cost = 0;
        }
        else $unit_cost = 0;
        return $unit_cost;
    }

    /*更新出入库流水的库存成本等字段和仓库货品表的库存成本和单位成本字段
    *@params $iodata出库流水数据 $unit_cost单位成本 $inventory_cost_total出库成本
    */
    function update_iostock($iodata=array(),$unit_cost='',$operator='',$inventory_cost_total='')
    {

        if(empty($iodata) || empty($operator)) return false;
        $iostock = app::get("ome")->model("iostock");

        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");

        $now_num = $iodata['balance_nums'];

        if($setting_stockcost_get_value_type == '2' && $iodata['type_id'] == '3'){
            $filter_sql = '';
        }else{
            $filter_sql = ',unit_cost = IF( ROUND(inventory_cost/store,3)>0,ROUND(inventory_cost/store,3),0 )';
        }

        $inventory_cost = $unit_cost*$iodata['nums'];
        $last_row = $iostock->db->selectrow("select store,inventory_cost,unit_cost from sdb_ome_branch_product  where product_id=".intval($iodata['product_id'])." and branch_id=".intval($iodata['branch_id']));

        //todo 这里不防并发问题不大
        if( $inventory_cost_total == '' ){
            $inventory_cost = $unit_cost*$iodata['nums']; //出入库成本
        }else{
            $inventory_cost = $inventory_cost_total;
        }

        switch($operator){
            case "+": //入库
                    $now_inventory_cost = $last_row['inventory_cost'] + $inventory_cost; //结存成本 = 仓库货品表的库存成本+入库成本
                    $branch_product_sql = " UPDATE sdb_ome_branch_product set inventory_cost = IF( (inventory_cost+$inventory_cost)>0 , inventory_cost+$inventory_cost ,0 ),unit_cost = IF( ROUND(inventory_cost/store,3)>0,ROUND(inventory_cost/store,3),0 )
 where branch_id=".intval($iodata['branch_id'])." and product_id=".intval($iodata['product_id']);

                break;
            case "-"://出库
                    //总仓单位信息与平均成本
                    $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();
                    if ($costSetting['branch_cost']['value'] == '2') {}else{
                        // 库存出完后取仓库库存成本，解决小数除不尽问题
                        if ($last_row['store'] == 0){
                            $inventory_cost = $last_row['inventory_cost'];
                        }
                    }

                    if($last_row['inventory_cost']<=0){
                        $now_inventory_cost = $inventory_cost;
                    }else{
                        $now_inventory_cost = $last_row['inventory_cost'] - $inventory_cost; //结存成本 = 仓库货品表的库存成本-入库成本
                    }

                    if($now_num)
                        $branch_product_sql = " UPDATE sdb_ome_branch_product set inventory_cost = IF( (inventory_cost-$inventory_cost)>0 , inventory_cost-$inventory_cost ,0 )".$filter_sql."
  where branch_id=".intval($iodata['branch_id'])." and product_id=".intval($iodata['product_id']);
                    else
                        $branch_product_sql = " UPDATE sdb_ome_branch_product set inventory_cost=0 where branch_id=".intval($iodata['branch_id'])." and product_id=".intval($iodata['product_id']);
        }

        if($now_num){
            $now_unit_cost = round($now_inventory_cost/$now_num,3);   //四舍五入 保留小数点两位
        }else{
            $now_unit_cost = $last_row['unit_cost'];
        }

        $iostock->db->exec($branch_product_sql) ;//更细仓库货品表的 库存成本和单位成本
        $iostock_update_data['unit_cost'] = ($unit_cost >0) ? $unit_cost :0;
        $iostock_update_data['inventory_cost'] = ($inventory_cost >0) ? $inventory_cost :0;
        $iostock_update_data['now_unit_cost'] = ($now_unit_cost >0) ? $now_unit_cost :0;
        $iostock_update_data['now_inventory_cost'] = ($now_inventory_cost >0) ? $now_inventory_cost:0;
        $iostock_update_data['now_num'] = $now_num ? $now_num :0;
        //总仓单位信息与平均成本
        $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();
        if ($costSetting['branch_cost']['value'] == '2') {
            $omeEntityBranchPorductLib                   = kernel::single('ome_entity_branch_product');
            $entityCostList                              = $omeEntityBranchPorductLib->getBranchCountCostPrice(intval($iodata['branch_id']), intval($iodata['product_id']));
            $iodata['unit_cost'] = $unit_cost;
            $setResult                                   = $omeEntityBranchPorductLib->setBranchCountCostPrice($iodata, $entityCostList, $operator);
            $iostock_update_data['entity_branch_detail'] = $omeEntityBranchPorductLib->getBranchRecordSerialize(intval($iodata['branch_id']), intval($iodata['product_id']));
            if ($setResult['store'] == 0) {
                $entityUnitCost = $setResult['unit_cost'];
                $inventory_cost = $setResult['inventory_cost'];
            }else{
                $entityUnitCost = $setResult['unit_cost'];
                $inventory_cost = $setResult['unit_cost'] * $iodata['nums'];
            }
        
            $iostock_update_data['entity_unit_cost']           = isset($entityUnitCost) ? $entityUnitCost : 0;
            $iostock_update_data['entity_inventory_unit_cost'] = isset($inventory_cost) ? $inventory_cost : 0;
        }
        $iostock->update($iostock_update_data, array("iostock_id" => $iodata['iostock_id']));
    }

    /*退货换货入库时取销售出库时的单位成本
    *@params $iostock_id 出入库流水iostock_id
    *@return 单位成本 float
    */
    function get_sale_unit_cost($data_v)
    {
        $iostock = app::get("ome")->model("iostock");
//        $original_item_id = $data_v['original_item_id'];
//
//        $return_row = $iostock->db->selectrow("select order_id,product_id,bn from sdb_ome_return_process_items where item_id =".intval($original_item_id));
//
//        $reship = $iostock->db->selectrow("select order_id from sdb_ome_reship where reship_id =".intval($return_row['reship_id']));

        $order_id = $data_v['order_id'];
        $product_id = $data_v['product_id'];
        $bn = $data_v['bn'];
        
        //成功的发货单
        $delivery_id_sql = "SELECT o.delivery_id,d.branch_id  FROM
                                `sdb_ome_delivery_order` AS o
                                LEFT JOIN `sdb_ome_delivery` AS d ON o.delivery_id = d.delivery_id
                                LEFT JOIN `sdb_ome_delivery_items` AS i ON d.delivery_id = i.delivery_id
                            WHERE
                                o.order_id = '" . $order_id . "' AND i.bn = '" . $bn . "' AND d.STATUS NOT IN ('failed','cancel','back','return_back')";
        $delivery = $iostock->db->selectrow($delivery_id_sql);
        

        $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();

        // 销售出库仓，当前总仓平均成本
        if ($costSetting['branch_cost']['value'] == '2'){
            return $this->get_product_unit_cost($product_id, $delivery['branch_id']);
        }

        $sql1 = 'select item_id,item_type from sdb_ome_order_items where order_id = '.$order_id.' and bn= "'.$bn.'"';
        $order_items = $iostock->db->selectrow($sql1);
        if ($order_items) {
            $sql2 = "select delivery_id,item_detail_id from sdb_ome_delivery_items_detail where delivery_id = ". $delivery['delivery_id'] ." and order_item_id = ".$order_items['item_id']." and order_id = ".$order_id." and item_type= '".$order_items['item_type']."'";

            $delivery_items_detail_row = $iostock->db->selectrow($sql2);

            $delivery_id = $delivery_items_detail_row['delivery_id'];

            $item_detail_id = $delivery_items_detail_row['item_detail_id'];

            $sql3 = "select unit_cost from sdb_ome_iostock where original_id=".intval($delivery_id)." and original_item_id=".intval($item_detail_id)." and type_id=3";

            $iostock_row = $iostock->db->selectrow($sql3);

            return $iostock_row['unit_cost'];
        }

    }

    /**生成先进先出数据
    *@params $data array() 出入库流水数据
    *@parmas $unit_cost float 入库单位成本
    *@return bool
    */
    function insert_fifo($data,$unit_cost)
    {
        $fifo = app::get("tgstockcost")->model("fifo");
        $fifo_sdf = array();
        $fifo_sdf['branch_id'] = $data['branch_id'];
        $fifo_sdf['product_id'] = $data['product_id'];
        $fifo_sdf['product_bn'] = $data['bn'];
        $fifo_sdf['stock_bn'] = $data['iostock_id'];
        $fifo_sdf['in_num'] = $data['nums'];
        $fifo_sdf['out_num'] = 0;
        $fifo_sdf['bill_bn'] = $data['original_bn'];
        $fifo_sdf['current_num'] = $data['nums'];
        $fifo_sdf['current_inventory_cost'] = $unit_cost*$data['nums'];
        $fifo_sdf['current_unit_cost'] = $unit_cost;
        return $fifo->save($fifo_sdf);
    }

    /**先进先出 出库 修改先进先出表数据
    *@params
    *@return array()
    */

    function fifo_stock($data)
    {
        if(!$data['nums'] && empty($data['nums'])) return false;
        $inventory_cost_total = 0;
        $data_nums = $data['nums'];
        $iostock = app::get("ome")->model("iostock");
        $concurrentModel = app::get('ome')->model('concurrent');//防并发表
        $whileNum = 0;
        while($data['nums']>0)
        {
            $fifo_first_row = $iostock->db->selectrow("select * from sdb_tgstockcost_fifo where branch_id=".intval($data['branch_id'])." and product_id=".intval($data['product_id'])." and in_num>0 and current_num>0 order by id ASC");
            if(empty($fifo_first_row)) break;
            $concurrentid = "F".$fifo_first_row['id']."I".$fifo_first_row['in_num']."F".$fifo_first_row['out_num']."O";
            if ($concurrentModel->is_pass($concurrentid,'IostockFiFo',false)){  //插入成功 可以操作
                if($fifo_first_row['current_num']>=$data['nums']){  //在库数量大于出库数量
                    $num = $data['nums'];
                    if($fifo_first_row['current_num'] > $data['nums']){
                        #如果没有用完，则修改数量和库存成本
                        $fifo_up_sql = "UPDATE sdb_tgstockcost_fifo set current_num=current_num-$num,out_num=out_num+$num,current_inventory_cost = current_unit_cost*current_num  where id=".intval($fifo_first_row['id']);
                    }elseif($fifo_first_row['current_num'] == $data['nums']){
                        #如果刚好用完，则删除记录
                        $fifo_up_sql = "delete from sdb_tgstockcost_fifo where id=".intval($fifo_first_row['id']) ;
                    }
                    $inventory_cost_total = $inventory_cost_total + $fifo_first_row['current_unit_cost']*$data['nums'];
                    $data['nums'] = 0;
                    $iostock->db->exec($fifo_up_sql);
                }else{
                    $data['nums'] = $data['nums']-$fifo_first_row['current_num'];
                    #跨批次使用的，把库存成本累加起来
                    $inventory_cost_total = $inventory_cost_total + $fifo_first_row['current_unit_cost']*$fifo_first_row['current_num'];

                    $delete_sql = "delete from sdb_tgstockcost_fifo where id=".intval($fifo_first_row['id']) ;
                    $iostock->db->exec($delete_sql);
                }

            }else{ //插入失败有进程试图修改  并发  等待再循环
                usleep(1000);
            }
            if($whileNum>3){ //重试三次，如果三次都失败则删除临时表已有数据
                $concurrentModel->delete(array('id'=>$concurrentid,'type'=>'IostockFiFo'));
                break;
            }
            $whileNum++;
        }
        $unit_cost = ($data_nums>0)?round($inventory_cost_total/$data_nums,3):0;

        $out_data['unit_cost'] = $unit_cost;
        $out_data['inventory_cost_total'] = $inventory_cost_total;
        return $out_data;
    }
    /* 货品的固定成本
    *@params $product_id 货品ID
    *@return float
    */
    function get_product_cost($product_id)
    {
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
	    $p_row    = $basicMaterialExtObj->dump(array('bm_id'=>intval($product_id)), '*');

        $unit_cost = $p_row['cost'] ? $p_row['cost'] :0;
        return $unit_cost;
    }

    /* 货品在仓库的平均成本
    *@params $product_id 货品ID $branch_id 仓库ID
    *@return float
    */
    function get_product_unit_cost($product_id,$branch_id)
    {
        $bpMdl = app::get("ome")->model("branch_product");
        $p_row = $bpMdl->db_dump([
            'product_id'        => $product_id,
            'branch_id'         => $branch_id,
            'check_permission'  => 'false',
        ], 'inventory_cost,unit_cost,store');
        //总仓单位信息与平均成本
        $costSetting = kernel::single('tgstockcost_system_setting')->getCostSetting();
        if ($costSetting['branch_cost']['value'] == '2') {
            $omeEntityBranchPorductLib = kernel::single('ome_entity_branch_product');
            $entityCostList            = $omeEntityBranchPorductLib->getBranchCountCostPrice(intval($branch_id), intval($product_id));
            $p_row                     = $entityCostList[$branch_id][$product_id];
        }
        return $p_row['unit_cost'] ?: 0;
    }

    /* 货品最近一次的出入库成本
    *@params $product_id 货品ID $branch_id 仓库ID
    *@return float
    */
    function get_last_product_unit_cost($product_bn,$branch_id,$product_id,$offset='1')
    {
        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        $iostock = app::get("ome")->model("iostock");
        $p_row = $iostock->db->select("select create_time,unit_cost from sdb_ome_iostock where branch_id=".intval($branch_id)." and bn='".$product_bn."' order by create_time desc limit " . $offset . ",1");
        if($p_row[0]['create_time']<$stockcost_install_time){//出入库时间小于APP安装时间 说明没有计算出入库成本 取仓库货品表
            $unit_cost = $this->get_product_unit_cost($product_id,$branch_id);
        }
        else{
            $unit_cost = $p_row[0]['unit_cost'] ? $p_row[0]['unit_cost'] :0;
        }
        return $unit_cost;
    }

    /*根据货品BN获取货品ID*/
    function get_product_id($bn)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $aData = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id');

        return $aData['bm_id'];
    }
}
