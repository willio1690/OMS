<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaninventory_inventorylist{

    /*
    *保存盘点明细
    *@param array data
    *$msg
    */

    function save_inventory($data,&$msg){
        $invObj = app::get('taoguaninventory')->model('inventory');
        /*盘点单日志*/
        $opObj  = app::get('ome')->model('operation_log');
        /**/
        $invitemObj = app::get('taoguaninventory')->model('inventory_items');
        $op_id   = kernel::single('desktop_user')->get_id();
        $pos_name = $data['pos_name'];
        $barcode = $data['barcode'];
        $branch_id = $data['branch_id'];
        $number     = $data['number'];
        $inventory_id = $data['inventory_id'];
        $product_id = $data['product_id'];

        if ( !$product_id ) {
            $msg = '商品不存在！';
            return false;
        }
        
        
        $is_use_expire    = $data['is_use_expire'];
        $expire_bn_info   = $data['expire_bn_info'];
        if($is_use_expire && empty($expire_bn_info))
        {
            $msg ='保质期信息不能为空';
            return false;
        }
        
        #商品和仓库关联
        $branch_product_result=$this->create_branch_product($branch_id,$product_id);
        if(!$branch_product_result){
            $msg='商品和仓库关联失败!';
            $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], $msg);
        }
        if($pos_name){//如果有货号
            $pos_id = $this->create_branch_product_pos($branch_id,$pos_name,$product_id);
        }else{
            $pos_id=0;
        }
        $data['pos_id'] = $pos_id;
        $db = kernel::database();
        $aDate = explode('-',date('Y-m-d'));
        $sqlstr = '';
        $sqlstr.=' AND io.pos_id='.$pos_id;
        $sql = 'SELECT inv.inventory_id,inv.difference,inv.op_id,inv.inventory_name,io.obj_id,io.item_id  FROM sdb_taoguaninventory_inventory as inv
                left join sdb_taoguaninventory_inventory_object as io on inv.inventory_id=io.inventory_id
        		WHERE inv.branch_id='.$branch_id.' AND inv.confirm_status=1 AND io.product_id='.$product_id.$sqlstr.'
        		ORDER BY inv.inventory_id';

        $inventory    = $db->selectRow($sql);
        
        #检查仓库+货位+货号是否已存在其它未确认盘点中
        if($inventory && $inventory['inventory_id'] != $inventory_id)
        {
            $msg    ='盘点名称：' . $inventory['inventory_name'] .',已存在此商品';
            return false;
        }
        
        /* 无用代码
        $accounts_num = $this->get_accounts_num($product_id,$branch_id);
        */
        
        if($inventory){
            //是否有同样的商品+仓库+货位
            if($inventory['op_id'] == $op_id){
                //更新明细信息
                $old_inventory_id = $data['inventory_id'];

                unset($data['inventory_id']);
                $data['inventory_id'] = $inventory['inventory_id'];
                $data['item_id'] = $inventory['item_id'];
                $data['num_over'] = 1;
                $result=$this->update_inventory_item($data);


                if(!$result){
                    $msg = '商品更新失败!';

                }else{
                    $msg ='因此商品已存在于未确认盘点表中，且是同一管理员操作,所以此次盘点添加商品数据覆盖';
                    if($old_inventory_id!=$data['inventory_id']){
                        $msg.='添加至'.$inventory['inventory_name'].'中';
                    }
                }

                return $result;
            }else{
                $msg ='此商品已存在于盘点列表中,请确认!';


                return false;
            }
        }else{
            $invitem = $invitemObj->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$product_id),'item_id');
            if($invitem){
                $data['item_id'] = $invitem['item_id'];
            }
            $result=$this->update_inventory_item($data);
            $this->update_inventorydifference($data['inventory_id']);
            $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], '更新盘点明细');

            return $result;

        }
    }

    /*
    *创建商品与货位关系
    */


    public function create_branch_product_pos($branch_id,$pos_name,$product_id){
        $oBranch_pos = app::get('ome')->model('branch_pos');
        $oBranch_product_pos = app::get('ome')->model('branch_product_pos');
        if($pos_name!=' '){//如果有货号
            $branch_pos = $oBranch_pos->getlist('*',array('branch_id'=>$branch_id,'store_position'=>$pos_name),0,1);
            if(!$branch_pos){
                $branch_pos_data = array();
                $branch_pos_data['branch_id'] = $branch_id;
                $branch_pos_data['store_position'] = $pos_name;
                $branch_pos_data['create_time'] = time();
                $result = $oBranch_pos->save($branch_pos_data);
                $pos_id = $branch_pos_data['pos_id'];
            }else{
                $pos_id = $branch_pos[0]['pos_id'];
            }

            $branch_product_pos = $oBranch_product_pos->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id,'pos_id'=>$pos_id),'*');
            if(!$branch_product_pos){
                $branch_product_pos_data = array();
                $branch_product_pos_data['branch_id'] = $branch_id;
                $branch_product_pos_data['product_id'] = $product_id;
                $branch_product_pos_data['pos_id']   = $pos_id;
                $branch_product_pos_data['create_time']   = time();
                $result = $oBranch_product_pos->save($branch_product_pos_data);
            }
            return $pos_id;
        }
    }

    /*
    *创建商品与仓库关系
    */

    public function create_branch_product($branch_id,$product_id){
        $oBranch_product  = app::get('ome')->model('branch_product');
        $branch_product = $oBranch_product->getlist('branch_id',array('branch_id'=>$branch_id,'product_id'=>$product_id),0,1);

        if(!$branch_product){
            $branch_product_data = array();
            $branch_product_data['branch_id'] = $branch_id;
            $branch_product_data['product_id'] = $product_id;

            $result = $oBranch_product->save( $branch_product_data );
        }
        return true;
    }

    /*
    * 获取商品账面数量
    * @param product_id branch_id
    * 注：保质期物料增加$item_id判断
    * 
    */

    public function get_accounts_num($product_id,$branch_id, $inventory_id=NULL, $item_id=NULL)
    {
        $libBranchProduct    = kernel::single('ome_branch_product');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        #保质期物料
        $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($product_id);
        if($is_use_expire)
        {
            if(!$inventory_id || !$item_id)
            {
                //$msg    = '保质期物料读取商品账面数量出错';
                return 0;
            }
            
            $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
            $oInventory_object    = app::get('taoguaninventory')->model('inventory_object');
            $inventoryList        = $oInventory_object->getList('obj_id, storage_life_info', array('inventory_id'=>$inventory_id, 'item_id'=>$item_id, 'product_id'=>$product_id));
            if(empty($inventoryList))
            {
                //$msg    = '没有保质期批次详细信息';
                return 0;
            }
            
            $expire_bn_list       = array();
            foreach ($inventoryList as $key => $val)
            {
                $storage_life_info    = unserialize($val['storage_life_info']);
                if(empty($storage_life_info))
                {
                    continue;
                }
                
                foreach ($storage_life_info as $key_j => $val_j)
                {
                    $expire_bn_list[]    = $val_j['expire_bn'];
                }
            }
            if(empty($expire_bn_list))
            {
                //$msg    = '没有找到有效保质期批次';
                return 0;
            }
            
            #所选的保质期批次号_入库总数
            $accounts_num        = 0;
            $filter              = array('bm_id'=>$product_id, 'branch_id'=>$branch_id, 'expire_bn|in'=>$expire_bn_list, 'status'=>1);
            $storageLifeBatch    = $basicMaterialStorageLifeObj->getList('bmsl_id, in_num, balance_num', $filter);
            foreach ($storageLifeBatch as $key => $val)
            {
                $accounts_num    += $val['balance_num'];
            }
        }
        else 
        {
            $branch_store = $libBranchProduct->getStoreByBranch($product_id,$branch_id);
            
            if($branch_store){
                $accounts_num  = $branch_store;
            }else{
                $accounts_num = 0;
            }
        }
        
        return $accounts_num;
    }

    /*
    *获取商品单价
    *新增如果设置了成本取成本设置值
    */
    /**
     * 获取_price
     * @param mixed $product_id ID
     * @param mixed $branch_id ID
     * @return mixed 返回结果
     */
    public function get_price($product_id,$branch_id)
    {
        $basicMaterialObj    = app::get('material')->model('basic_material');
        
        $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");
        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");
        
        $tgstockcost = kernel::single("tgstockcost_taog_instance");
        $price = 0;
        if($setting_stockcost_get_value_type){
            $iostock = app::get("ome")->model("iostock");
            
            if($setting_stockcost_get_value_type == '1'){ //取货品的固定成本
                $price = $tgstockcost->get_product_cost($product_id);
            }
            elseif($setting_stockcost_get_value_type == '2'){ //取货品的单位平均成本  to 如果仓库货品表没有记录？
                $price = $tgstockcost->get_product_unit_cost($product_id,$branch_id);
            }
            elseif($setting_stockcost_get_value_type == '3'){//取货品的最近一次出入库成本  to 如果在该仓库下没有出入库记录？
                #
                $product     = $basicMaterialObj->dump( array('bm_id'=>$product_id), '*');
                
                $product_bn = $product['material_bn'];
                $price = $tgstockcost->get_last_product_unit_cost($product_bn,$branch_id,$product_id,0);
            }
            elseif($setting_stockcost_get_value_type == '4'){//取0
                $price = 0;
            }
        }else{

            if(app::get('purchase')->is_installed()){
               $poObj  = app::get('purchase')->model('po');
               $price = $poObj->getPurchsePrice($product_id,'DESC');
               if(!$price){
                   $price = 0;
               }
           }else{
               $price = 0;
           }


        }
         
        
        return $price;
    }

    /*
    *获取盘点明细里商品实际数量总计
    */
    private function get_inventory_bybn($inventory_id,$product_id){
        $db = kernel::database();
        $sql = 'SELECT sum(actual_num) as actual_num FROM sdb_taoguaninventory_inventory_object
                WHERE inventory_id='.$inventory_id.' AND product_id='.$product_id;
        $inventory_obj = $db->selectrow($sql);
        return $inventory_obj['actual_num'];
    }

    /*
    *更新盘点单明细
    */

    public function update_inventory_item($data)
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        
        $opObj  = app::get('ome')->model('operation_log');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        
        $products     = $basicMaterialLib->getBasicMaterialExt($data['product_id']);
        
       if($products){
            $data = array_merge($data,$products);
       }
       $inv_item_data = array();
       $inv_item_data['inventory_id'] = $data['inventory_id'];
        $inv_item_data['product_id'] = $data['product_id'];
        $inv_item_data['price'] = $price;
        $inv_item_data['availability'] = 'true';
        $inv_item_data['memo'] = '在线盘点，新增商品数量';
        $inv_item_data['oper_time'] = time();
       if($data['item_id']){
            $inv_item_data['item_id'] = $data['item_id'];
        }else{
            $inv_item = $oInventory_items->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$data['product_id']),'item_id,actual_num');
            if($inv_item){
                $inv_item_data['item_id'] = $inv_item['item_id'];
            }
            $inv_item_data['name'] = $data['material_name'];
            $inv_item_data['bn'] = $data['material_bn'];

            $inv_item_data['spec_info'] = $data['spec_info'];
            $inv_item_data['unit'] = $data['unit'];
       }
       $inv_item_data['barcode'] = $data['barcode'];
       $inv_item_data['is_auto'] = $data['is_auto']=='1' ? '1':'0';
       $item_result = $oInventory_items->save($inv_item_data);

        if(!$item_result){
            $msg='明细表保存失败';

        }
       $data['item_id'] = $inv_item_data['item_id'];
       $total = 0;
       $obj_result = $this->create_inventory_obj($data);
        if(!$obj_result){
           $msg= 'obj表创建失败!';
           $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], $msg);
        }

        return $obj_result;
   }

    /*
    *创建盘点单中间表
    * @param array
    *
    */

    public function create_inventory_obj($data){
       $oInventory_object = app::get('taoguaninventory')->model('inventory_object');
       $oInventory_items = app::get('taoguaninventory')->model('inventory_items');

       if($data['pos_name'] && $data['pos_id']==''){
            $data['pos_id'] = $this->create_branch_product_pos($data['branch_id'],$data['pos_name'],$data['product_id']);
        }
       $inv_obj=$oInventory_object->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$data['product_id'],'pos_id'=>$data['pos_id']),'item_id,obj_id');
        $inv_object = array();
        if($data['obj_id']){
            $inv_object['obj_id'] = $data['obj_id'];
        }
        if($data['num_over']==1){//数量是否覆盖标识
            if($inv_obj){
                $inv_object['obj_id'] = $inv_obj['obj_id'];
            }
        }
        $inv_object['oper_id'] = kernel::single('desktop_user')->get_id();;
        $inv_object['oper_name'] = kernel::single('desktop_user')->get_name();
        $inv_object['oper_time'] = time();
        $inv_object['inventory_id'] = $data['inventory_id'];
        $inv_object['product_id'] = $data['product_id'];
        $inv_object['pos_id'] = $data['pos_id'];
        $inv_object['bn'] = $data['bn'];
        $inv_object['barcode'] = $data['barcode'];
        $inv_object['pos_name'] = $data['pos_name'];
        $inv_object['actual_num'] = $data['number'];
        $inv_object['item_id'] = $data['item_id'];
        
        #保质期关联信息
        $inv_object['storage_life_info']    = $data['expire_bn_info'];
        
        $result = $oInventory_object->save($inv_object);
        if($result){
            #成本价
            $price = $this->get_price($data['product_id'],$data['branch_id']);
            $inv_item_data['price'] = $price;
            
            $inv_item_data['accounts_num'] = $this->get_accounts_num($data['product_id'],$data['branch_id'], $data['inventory_id'], $data['item_id']);
            $inv_item_data['actual_num'] = kernel::single('taoguaninventory_inventorylist')->get_inventory_bybn($data['inventory_id'],$data['product_id']);
            $inv_item_data['shortage_over'] = $inv_item_data['actual_num']-$inv_item_data['accounts_num'];
            $inv_item_data['item_id']= $data['item_id'];
            $oInventory_items->save($inv_item_data);

        }
        return $result;
   }


    /*
    * 创建盘点表
    * @param data
    *
    */
    function create_inventory($data,&$msg){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $opObj  = app::get('ome')->model('operation_log');

        $op_name = kernel::single('desktop_user')->get_name();
        $op_id   = kernel::single('desktop_user')->get_id();
        $oEncoded_state = app::get('taoguaninventory')->model('encoded_state');
        $get_state = $oEncoded_state->get_state('inventory');
        if(!$get_state){
            $msg='编码表信息不存在';
            return false;
        }
        $inventory_checker = $data['inventory_checker']=='' ? $op_name : $data['inventory_checker'];
        $second_checker    = $data['second_checker']=='' ? $op_name : $data['second_checker'];
        $finance_dept    = $data['finance_dept']=='' ? $op_name : $data['finance_dept'];
        $warehousing_dept    = $data['warehousing_dept']=='' ? $op_name : $data['warehousing_dept'];
        $op_id = $op_id ? $op_id : -1;
        $inv['inventory_name']      = $data['inventory_name'];
        $inv['inventory_bn']        = $get_state['state_bn'];
        $inv['inventory_date']      = time();
        $inv['add_time'] = strtotime($data['add_time']);
        $inv['inventory_checker']   = $inventory_checker;
        $inv['second_checker']      = $second_checker;
        $inv['finance_dept']        = $finance_dept;
        $inv['warehousing_dept']    = $warehousing_dept;
        $inv['op_name']             = $op_name;
        $inv['op_id']               = $op_id;
        $inv['branch_id']           = $data['branch_id'];
        $inv['branch_name']         = $data['branch_name'];
        $inv['inventory_type']      = $data['inventory_type'];
        $inv['pos'] = $data['pos'];
        $inv['memo'] = $data['memo'];
        $inv['inventory_type'] = $data['inventory_type'];
        $result = $oInventory->save($inv);
        if($result){
            $encoded_state_data = array();
            $encoded_state_data['currentno'] = $get_state['currentno'];
            $encoded_state_data['eid'] = $get_state['eid'];
            $oEncoded_state->save($encoded_state_data);
            //补全
            if($data['inventory_type']==2){
                $this->auto_product_list($inv['inventory_id'],$data['branch_id']);
            }
        }

       $opObj->write_log('inventory_modify@taoguaninventory', $inv['inventory_id'], '创建盘点单');
       return $inv['inventory_id'];

   }

   /*
   * 获得商品货位
   * @param data
   * return data
   */
   function get_product_pos($data){
        $oProduct_pos = app::get('ome')->model('branch_product_pos');
        $oBranch_pos= app::get('ome')->model('branch_pos');
        $product_pos_list = $oProduct_pos->getlist('pos_id,product_id',array('product_id'=>$data['product_id']),0,-1);
        foreach($product_pos_list as $k=>$v){
            $branch_pos = $oBranch_pos->dump(array('pos_id'=>$v['pos_id']),'store_position');
            $product_pos_list[$k]['pos_name'] = $branch_pos['store_position'];
        }
        return $product_pos_list;

   }

    /*
     *删除盘点明细
     *@param array
     *return boolean
    */
    function del_inventory($data)
    {
       $act = $data['action'];
       $oInventory = app::get('taoguaninventory')->model('inventory');
       $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
       $oinventory_object = app::get('taoguaninventory')->model('inventory_object');
       
       $obj_id = intval($data['obj_id']);
       $item_id = intval($data['item_id']);
       $inventory_id = intval($data['inventory_id']);
       $bmsl_id      = intval($data['bmsl_id']);

       $inventory = $oInventory->dump($inventory_id,'branch_id,inventory_type');
       $items = $oInventory_items->dump(array('inventory_id'=>$inventory_id,'item_id'=>$item_id),'product_id');
       switch($act){
           case 'item':
                $result = $oInventory_items->delete(array('inventory_id'=>$inventory_id,'item_id'=>$item_id));
                if($result){
                    $oinventory_object->delete(array('inventory_id'=>$inventory_id,'item_id'=>$item_id));
                }
                if($inventory['inventory_type']=='2'){
                    $product_add = array();
                    $product_add['inventory_id']=$inventory_id;
                    $product_add['branch_id']=$inventory['branch_id'];
                    $product_add['product_id']=$items['product_id'];
                    $product_add['number']=0;
                    $product_add['is_auto']='1';

                    $this->update_inventory_item($product_add);
                }
               break;
           case 'obj':
               $inventory_object = $oinventory_object->dump($obj_id,'inventory_id,product_id,actual_num,item_id, storage_life_info');
               
               #盘点对应保质期明细删除
               if($bmsl_id)
               {
                   $update_object    = array();
                   $storage_life_info    = unserialize($inventory_object['storage_life_info']);
                   foreach ($storage_life_info as $key_j => $val_j)
                   {
                       if($val_j['bmsl_id'] != $bmsl_id)
                       {
                           $update_object['storage_life_info'][]    = $val_j;
                           
                           $update_object['actual_num']    += $val_j['in_num'];
                       }
                   }
                   
                   #保存剩余盘点明细
                   if($update_object['storage_life_info'])
                   {
                       $oinventory_object->update($update_object, array('obj_id'=>$obj_id));
                   }
                   else 
                   {
                       $oinventory_object->delete(array('inventory_id'=>$inventory_id,'obj_id'=>$obj_id));
                   }
               }
               else 
               {
                   $oinventory_object->delete(array('inventory_id'=>$inventory_id,'obj_id'=>$obj_id));
               }
               
               $item_id    = $inventory_object['item_id'];
               $product_id = $inventory_object['product_id'];
               $branch_id = $inventory['branch_id'];
               $item_actual_num = $this->get_inventory_bybn($inventory_id,$product_id);

               if($item_actual_num==0){
                    $oInventory_items->delete(array('inventory_id'=>$inventory_id,'product_id'=>$product_id));
                    if($inventory['inventory_type']=='2'){
                        $product_add = array();
                        $product_add['inventory_id']=$inventory_id;
                        $product_add['branch_id']=$inventory['branch_id'];
                        $product_add['product_id']=$product_id;
                        $product_add['number']=0;
                        $product_add['is_auto']='1';

                        $this->update_inventory_item($product_add);
                    }

               }else{
                   #获取商品账面数量
                   $accounts_num    = $this->get_accounts_num($product_id, $branch_id, $inventory_id, $item_id);
                   
                   /*
                    $libBranchProduct    = kernel::single('ome_branch_product');
                    $branch_store = $libBranchProduct->getStoreByBranch($product_id,$branch_id);
                    if($branch_store){
                     $accounts_num  = $branch_store;
                    }else{
                     $accounts_num = 0;
                    }
                    */
                   
                    $items_data = array();
                    $items_data['item_id'] = $item_id;
                    $items_data['inventory_id'] = $inventory_object['inventory_id'];
                    $items_data['actual_num'] = $item_actual_num;
                    $items_data['accounts_num'] = $accounts_num;
                    $items_data['shortage_over'] = $item_actual_num-$accounts_num;
                    $oInventory_items->save($items_data);

               }
            break;
        }
    }

    /**
     * 刷新盘点单预盈亏
     */
    function refresh_shortage_over($inventory_id,$branch_id){
        #判断如果状态为已确认不刷新 避免打开的页面重复预盈亏
        $inventory_detail = app::get('taoguaninventory')->model('inventory')->dump($inventory_id, 'confirm_status');
        $confirm_status = $inventory_detail['confirm_status'];
        if ($confirm_status=='1'){
            $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
            $inv_item = $oInventory_items->getlist('name,bn,spec_info,item_id,unit,price,memo,actual_num,shortage_over,accounts_num,product_id',array('inventory_id'=>$inventory_id,'is_auto'=>'0'));
            foreach($inv_item as $k=>$v){
                $item_data = array();
                $accounts_num = $this->get_accounts_num($v['product_id'],$branch_id, $inventory_id, $v['item_id']);
                $item_data ['accounts_num'] = $accounts_num;
                $item_data ['shortage_over']  = $v['actual_num']-$accounts_num;
                $item_data ['item_id']  = $v['item_id'];

                $result = $oInventory_items->save($item_data);

            }
        }
        return true;
    }


    /**
     * 确认盘点单
     * @access public
     * @param  $data $msg
     * @return boolean
     */
    public function confirm_inventory($data,&$msg){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $iostock_instance = kernel::service('taoguan.iostock');
        
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $pagelimit = 100;
        $total = $oInventory->getInventoryTotal($data['inventory_id']);
        $inventory = $oInventory->getlist('inventory_type',array('inventory_id'=>$data['inventory_id']),0,1);
        $inventory_type = $inventory[0]['inventory_type'];
        $page = ceil($total['count']/$pagelimit);
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录

            if(count($total)<0){
                $msg='当前盘点单中无可以入库的商品';
                return false;
            }
            //
          for($i=1;$i<=$page;$i++){

            $inventory = array();//盘亏
            $overage = array();//盘盈
            $iostock_data = array();
            $default_store = array();
            $inventory_data = $oInventory_items->getList('item_id,product_id,bn,price,shortage_over,actual_num,accounts_num', array('inventory_id'=>$data['inventory_id']), $pagelimit*($i-1), $pagelimit,'item_id desc');
            foreach($inventory_data as $k=>$v){
                #
                $items = array();
                $items['item_id'] = $v['item_id'];
                $accounts_num = $this->get_accounts_num($v['product_id'],$data['branch_id'], $data['inventory_id'], $v['item_id']);
                $items['accounts_num'] = $accounts_num;
                $shortage_over = $v['actual_num']-$accounts_num;
                #如果账面数量有变更更新记录
                if($v['accounts_num']!=$accounts_num){
                    $oInventory_items->save($items);
                }
                #
                $iostock_data= array(
                    'branch_id' => $data['branch_id'],
                    'original_bn' => $data['inventory_bn'],
                    'original_id' => $data['inventory_id'],
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $shortage_over,
                    'oper' => $data['inventory_checker'],
                    'create_time' => $data['inventory_date'],
                    'operator' => $data['op_name'],
                    'memo' => $data['memo'],
                );
                if($inventory_type=='4'){
                    $iostock_data['nums'] = abs($iostock_data['nums']);
                    $default_store[$v['item_id']] = $iostock_data;
                }else{
                    if($shortage_over>0){
                        $overage[$v['item_id']] = $iostock_data;
                    }else{
                        $iostock_data['nums'] = abs($iostock_data['nums']);
                        $inventory[$v['item_id']] = $iostock_data;
                    }
                }
            }
            if(count($default_store)>0){
                eval('$type='.get_class($iostock_instance).'::DEFAULT_STORE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $default_store, $type, $default_store_msg, $io);
            }
            if(count($overage)>0){
                eval('$type='.get_class($iostock_instance).'::OVERAGE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $overage, $type, $overage_msg, $io);

            }
            if(count($inventory)>0) {

                eval('$type='.get_class($iostock_instance).'::INVENTORY;');
                $iostock_bn = $iostock_instance->get_iostock_bn($type);
                $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $inventory, $type, $inventory_msg, $io);

            }
         }
//
            if($result){
                $inventory_data = array(
                    'inventory_id' => $data['inventory_id'],
                    'confirm_status'=>2,
                );
                $oInventory->save($inventory_data);

            }
            return true;
        }


    }

    /**
     * 组织出库数据
     * @access public
     * @param  $inventory_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($inventory_id){

        $invitemObj = app::get('taoguaninventory')->model('inventory_items');
        
        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaninventory_inventory` WHERE `inventory_id`=\''.$inventory_id.'\'';
        $inventory_detail = $db->selectrow($sql);
        $inv_items_detail = $invitemObj->getList('*', array('inventory_id'=>$inventory_id), 0, -1);
        if ($inv_items_detail){
            foreach ($inv_items_detail as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'branch_id' => $inventory_detail['branch_id'],
                    'original_bn' => $inventory_detail['inventory_bn'],
                    'original_id' => $inventory_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['shortage_over'],
                    'oper' => $inventory_detail['inventory_checker'],
                    'create_time' => $inventory_detail['inventory_date'],
                    'operator' => $inventory_detail['op_name'],
                    'memo' => $inventory_detail['memo'],
                );
            }
        }
        return $iostock_data;
    }

    /*
    * 更新盘点单差异值
    */
    function update_inventorydifference($inventory_id){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory_items = $oInventory_items->getlist('shortage_over,price',array('inventory_id'=>$inventory_id),0,-1);
        $total = 0 ;
        foreach( $inventory_items as $k=>$v) {
            $total +=$v['shortage_over']*$v['price'];
        }
        $inventory_data = array(
            'inventory_id' => $inventory_id,
            'difference'    => $total
        );
        $result = $oInventory->save($inventory_data);
        return $result;
    }

    /*
    *更新盘点单状态值
    */
    function updateinventorystatus($data){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $inventory_data = array(
            'inventory_id'  => $data['inventory_id'],
        );
        if($data['import_status']){
            $inventory_data['import_status'] = $data['import_status'];
        }
        if($data['update_status']){
            $inventory_data['update_status'] = $data['update_status'];
        }
        $oInventory->save($inventory_data);
    }

    /**
     * 查询此货品是否可以操作
     */
    function checkproductoper($product_id,$branch_id=''){
        $db = kernel::database();
        $sql = 'SELECT count(i.bn) as count FROM sdb_taoguaninventory_inventory_items as i
                    left join sdb_taoguaninventory_inventory as inv on i.inventory_id=inv.inventory_id
                    WHERE i.product_id=\''.$product_id.'\' AND inv.branch_id='.$branch_id.' AND inv.confirm_status=1 ';

        $product = $db->selectrow($sql);
        if($product['count']>0){

            return false;
        }else{
            return true;
        }

    }

    function get_reset_product_list($inventory_id,$branch_id,$inventory_items){
        set_time_limit(0);
        $inventoryItemsObj = app::get('taoguaninventory')->model('inventory_items');
        $db = kernel::database();
        if($inventory_items){
            $product_id_list = array();
            foreach($inventory_items as $k=>$v){
                $product_id_list[] = $v['product_id'];
            }

            $product_id_list = implode(',',$product_id_list);
            $sqlstr.=' AND product_id not in ('.$product_id_list.')';
          }else{
            $sqlstr.='';
          }
          $pagelimit = 100;

          $product = $db->selectrow('SELECT count(product_id) as count FROM sdb_ome_branch_product
                            WHERE branch_id='.$branch_id.$sqlstr);
          $total = $product['count'];
           $page = ceil($total/$pagelimit);
           for($i=1;$i<=$total;$i++){
                $offset = $pagelimit*($i-1);
                $offset = max($offset,0);
            $product_id_list_sql = 'SELECT product_id,store FROM sdb_ome_branch_product
                            WHERE branch_id='.$branch_id.$sqlstr.' LIMIT '.$offset .','.$pagelimit;

            $product_id_list = $db->select($product_id_list_sql);

             foreach( $product_id_list as $pk=>$pv){
                $product_list = array();
                $product_list['product_id'] = $pv['product_id'];

                //$accounts_num = $this->get_accounts_num($pv['product_id'],$branch_id);
                $accounts_num = $pv['store'];
                if($accounts_num>0){
                    $product_list['number'] = 0;
                    $product_list['branch_id'] = $branch_id;
                    $product_list['is_auto']='1';
                    $product_list['inventory_id'] = $inventory_id;

                    $invitem = $inventoryItemsObj->dump(array('inventory_id'=>$inventory_id,'product_id'=>$pv['product_id']),'item_id');
                    if(!$invitem){
                        $this->update_inventory_item($product_list);
                    }
                }
             }
           }
    }

    function auto_product_list($inventory_id,$branch_id){
        $db = kernel::database();
        $db->beginTransaction();

        $oper_id = kernel::single('desktop_user')->get_id();
        $oper_name = kernel::single('desktop_user')->get_name();
        
        #第一步：先盘点有库存的普通物料
        $sqlstr = 'SELECT '.$inventory_id.' AS inventory_id, \'1\' as is_auto, 
                   a.bm_id AS product_id, a.material_name AS name, a.material_bn AS bn, 
                   bp.store as accounts_num,0 as actual_num, CAST(0-bp.store AS SIGNED) AS shortage_over,'.time().' as oper_time 
                   FROM sdb_material_basic_material AS a LEFT JOIN sdb_ome_branch_product AS bp ON a.bm_id=bp.product_id 
                   LEFT JOIN sdb_material_basic_material_conf AS bmc ON a.bm_id = bmc.bm_id
                   WHERE bp.branch_id='.$branch_id.' AND bp.store>0 AND bmc.use_expire =2';
        
        $item_sql = 'INSERT INTO sdb_taoguaninventory_inventory_items(inventory_id,is_auto,product_id,`name`,bn,accounts_num,actual_num,shortage_over,oper_time) '.$sqlstr;
        $item_result = $db->exec($item_sql);
        if($item_result ){
            $item_obj_sql = 'INSERT INTO sdb_taoguaninventory_inventory_object(inventory_id,item_id,product_id,bn,actual_num,oper_time,oper_id,oper_name) SELECT '.$inventory_id.' as inventory_id,i.item_id as item_id,i.product_id,i.bn,i.actual_num,'.time().' as oper_time,'.$oper_id.' as oper_id,\''.$oper_name.'\' as oper_name FROM sdb_taoguaninventory_inventory_items AS i  WHERE i.inventory_id='.$inventory_id;
            $obj_result = $db->exec($item_obj_sql);
            if($obj_result)
            {
                #第二步：再盘点有库存的保质期物料
                $sqlstr    = 'SELECT '.$inventory_id.' AS inventory_id, \'1\' as is_auto,
                               a.bm_id AS product_id, a.material_name AS name, a.material_bn AS bn,
                               bp.store as accounts_num,0 as actual_num, -bp.store AS shortage_over,'.time().' as oper_time
                               FROM sdb_material_basic_material AS a LEFT JOIN sdb_ome_branch_product AS bp ON a.bm_id=bp.product_id
                               LEFT JOIN sdb_material_basic_material_conf AS bmc ON a.bm_id = bmc.bm_id
                               WHERE bp.branch_id='.$branch_id.' AND bp.store>0 AND bmc.use_expire =1';
                $dataList    = kernel::database()->select($sqlstr);
                if(empty($dataList))
                {
                    $db->commit();#没有保质期_直接提交
                    return true;
                }
                
                #获取有效保质期的基础物料
                $basicMStorageLifeLib    = kernel::single('material_storagelife');
                $oInventory_items        = app::get('taoguaninventory')->model('inventory_items');
                $oinventory_object       = app::get('taoguaninventory')->model('inventory_object');
                
                foreach ($dataList as $key => $val)
                {
                    $storageLifeInfo    = $basicMStorageLifeLib->getStorageLifeBatchList($val['product_id'], $branch_id);
                    if(empty($storageLifeInfo))
                    {
                        continue;
                    }
                    
                    $accounts_num         = 0;#帐面数量
                    $storage_life_info    = array();
                    foreach ($storageLifeInfo as $key_j => $ex_item)
                    {
                        $accounts_num    += $ex_item['balance_num'];
                        
                        $storage_life_info[]    = array('bmsl_id'=>$ex_item['bmsl_id'], 'expire_bn'=>$ex_item['expire_bn'], 'in_num'=>0);#全盘_默认为0
                    }
                    
                    #创建盘点明细
                    $item_data    = array(
                                        'inventory_id'=>$inventory_id,
                                        'is_auto'=>1,
                                        'product_id'=>$val['product_id'],
                                        'name'=>$val['name'],
                                        'bn'=>$val['bn'],
                                        'accounts_num'=>$accounts_num,
                                        'actual_num'=>0,
                                        'shortage_over' => -$accounts_num,
                                        'oper_time'=>time(),
                                    );
                    $item_result    = $oInventory_items->save($item_data);
                    if(!$item_result)
                    {
                        $db->rollBack();
                        return false;
                    }
                    
                    #创建盘点中间记录
                    $obj_data    = array(
                                        'inventory_id'=>$inventory_id,
                                        'item_id'=>$item_data['item_id'],
                                        'product_id'=>$val['product_id'],
                                        'bn'=>$val['bn'],
                                        'actual_num'=>0,
                                        'oper_time'=>time(),
                                        'oper_id'=>$oper_id,
                                        'oper_name'=>$oper_name,
                                        'storage_life_info' => $storage_life_info,
                                    );
                    $obj_result    = $oinventory_object->save($obj_data);
                    if(!$obj_result)
                    {
                        $db->rollBack();
                        return false;
                    }
                }
                
                $db->commit();#确认提交
            }
            else
            {
                $db->rollBack();
            }
        }else{
            $db->rollBack();
        }

    }

    function hide_add_product_list($inventory_id,$inventory_type,$branch_id){
        $inventoryItemsObj = app::get('taoguaninventory')->model('inventory_items');
        if($inventory_type==2){//全盘
            $items = $inventoryItemsObj->getlist('item_id,product_id',array('inventory_id'=>$inventory_id),0,-1);
            $product_id_list = array();

            foreach($items as $k=>$v){

                 array_push($product_id_list,$v['product_id']);
            }

            $sqlstr = '';
            if($product_id_list){

                $product_id_list = implode(',',$product_id_list);
                $sqlstr.=' AND product_id not in ('.$product_id_list.')';
             }
                $product_id_list_sql = 'SELECT product_id FROM sdb_ome_branch_product as bp LEFT JOIN sdb_material_basic_material_conf AS bmc ON bp.product_id = bmc.bm_id WHERE store>0 AND bmc.use_expire =2 AND branch_id='.$branch_id.$sqlstr;
                $add_product_id_list = kernel::database()->select($product_id_list_sql);
                if($add_product_id_list){
                    foreach($add_product_id_list as $key=>$val){
                        $product_add = array();
                        $product_add['inventory_id']=$inventory_id;
                        $product_add['branch_id']=$branch_id;
                        $product_add['product_id']=$val['product_id'];
                        $product_add['number']=0;
                        $product_add['is_auto']='1';
                        $this->update_inventory_item($product_add);
                    }
                }

        }else if($inventory_type==3){//部分
            $items = $inventoryItemsObj->getlist('item_id',array('inventory_id'=>$inventory_id,'is_auto'=>'1'),0,-1);
            foreach($items as $k=>$v){
                $del_data = array(
                    'action'=>'item',
                    'inventory_id'=>$inventory_id,
                    'item_id'=>$v['item_id']

                );

                $this->del_inventory($del_data);
            }

        }

    }

    /**
     * 测试当类型为期初时，仓库是否有进出库记录
     */
    function check_product_iostock($branch_id){
        $iostockObj = app::get('ome')->model('iostock');
        $iostock = $iostockObj->getlist('branch_id',array('branch_id'=>$branch_id),0,1);
        if($iostock){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 
     */
    function get_inventorybybranch_id($branch_id){
        $inventoryObj = app::get('taoguaninventory')->model('inventory');
        $inventory = $inventoryObj->getlist('inventory_id',array('branch_id'=>$branch_id));

        if($inventory){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 
     */
    function doajax_inventorylist($data,$itemId,&$fail,&$succ,&$fallinfo){

        $oInventory = app::get('taoguaninventory')->model('inventory');
        $iostock_instance = kernel::service('taoguan.iostock');

        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory = $oInventory->getlist('inventory_type',array('inventory_id'=>$data['inventory_id']),0,1);
        $inventory_type = $inventory[0]['inventory_type'];

        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            foreach($itemId as $item_id){
                $inventory = array();//盘亏
                $overage = array();//盘盈
                $iostock_data = array();
                $default_store = array();
                kernel::database()->beginTransaction();

                $item_id = explode('||',$item_id);

                $item_id = $item_id[1];

                $items_data = $oInventory_items->getList('item_id,product_id,bn,price,shortage_over,actual_num,accounts_num,status', array('inventory_id'=>$data['inventory_id'],'item_id'=>$item_id));
                if($items_data[0]['status']=='true'){
                    //$succ++;
                    kernel::database()->commit();
                    continue;
                }
                $items = array();
                $items['item_id'] = $item_id;
                $accounts_num = $this->get_accounts_num($items_data[0]['product_id'],$data['branch_id'], $data['inventory_id'], $item_id);
                $items['accounts_num'] = $accounts_num;
                $shortage_over = $items_data[0]['actual_num']-$accounts_num;
                #如果账面数量有变更更新记录

                #更新盘点单明细为已盘点
                $item_result = kernel::database()->exec('UPDATE sdb_taoguaninventory_inventory_items SET `status`=\'true\',accounts_num='.$accounts_num.' WHERE item_id='.$item_id.' AND `status`="false"');
                if(!$item_result){
                    $fallinfo[] = '更新盘点明细状态失败，请联系管理员确认!';
                    
                    kernel::database()->rollBack(); continue;
                }
                #
                $iostock_data= array(
                    'branch_id' => $data['branch_id'],
                    'original_bn' => $data['inventory_bn'],
                    'original_id' => $data['inventory_id'],
                    'original_item_id' => $item_id,
                    'supplier_id' => 0,
                    'bn' => $items_data[0]['bn'],
                    'iostock_price' => $items_data[0]['price'],
                    'nums' => $shortage_over,
                    'oper' => $data['inventory_checker'],
                    'create_time' => $data['inventory_date'],
                    'operator' => $data['op_name'],
                    'memo' => $data['memo'],
                );


                if($inventory_type=='4'){
                    $iostock_data['nums'] = abs($iostock_data['nums']);
                    $default_store[$item_id] = $iostock_data;
                }else{
                    if($shortage_over>0){
                        $overage[$item_id] = $iostock_data;
                    }else{
                        $iostock_data['nums'] = abs($iostock_data['nums']);
                        $inventory[$item_id] = $iostock_data;
                    }
                }

                if(count($default_store)>0){
                eval('$type='.get_class($iostock_instance).'::DEFAULT_STORE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $default_store, $type, $default_store_msg, $io);
                }
                if(count($overage)>0){
                    eval('$type='.get_class($iostock_instance).'::OVERAGE;');
                     $iostock_bn = $iostock_instance->get_iostock_bn($type);
                     $io = $iostock_instance->getIoByType($type);
                    $result = $iostock_instance->set($iostock_bn, $overage, $type, $overage_msg, $io);

                }
                if(count($inventory)>0){

                    eval('$type='.get_class($iostock_instance).'::INVENTORY;');
                    $iostock_bn = $iostock_instance->get_iostock_bn($type);
                    $io = $iostock_instance->getIoByType($type);
                    $result = $iostock_instance->set($iostock_bn, $inventory, $type, $inventory_msg, $io);

                }

                if($result){
                    $succ++;
                    kernel::database()->commit();

                }else{
                    $fail++;

                    $fallinfo[] = $items_data[0]['bn'];

                    kernel::database()->rollBack();
                }

            }
            return true;
        }

    }

    function ajax_inventorylist($inventory_id){
        $inventory_items = app::get('taoguaninventory')->model('inventory_items')->getList('item_id', array('inventory_id'=>$inventory_id,'status'=>'false'), 0, -1,'item_id desc');
        $item_id = array();
        
        foreach ($inventory_items as $inventory){
            $item_id[] = $inventory['item_id'];
        }
        return $item_id;

    }
    
    /*
     * 保质期物料_盘点库存操作
     */

    public function update_storage_life_store($inventory_id, $branch_id, $expire_data)
    {
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        $basicMReceiptStorageLifeLib    = kernel::single('material_receipt_storagelife');
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_object    = app::get('taoguaninventory')->model('inventory_object');
        
        $item_ids    = $expire_data['item_id'];
        $bm_ids      = $expire_data['bm_id'];
        
        #盘点信息
        $inventory        = $oInventory->dump(array('inventory_id'=>$inventory_id), 'inventory_bn');
        
        #盘点中间明细
        $filter           = array('inventory_id'=>$inventory_id, 'item_id'=>$item_ids, 'product_id'=>$bm_ids);
        $inventoryList    = $oInventory_object->getList('obj_id, product_id, storage_life_info', $filter);
        
        if(empty($inventoryList))
        {
            return false;
        }
        
        $storage_life_list   = array();
        foreach ($inventoryList as $key => $val)
        {
            $bm_id    = $val['product_id'];
            
            $storage_life_info    = unserialize($val['storage_life_info']);
            if(empty($storage_life_info))
            {
                continue;
            }
            
            foreach ($storage_life_info as $ex_key => $ex_val)
            {
                #获取保质期批次号_账面数量
                $inv_filter		= array('branch_id'=>$branch_id, 'product_id'=>$bm_id, 'expire_bn'=>$ex_val['expire_bn']);
                $storage_life_num	= $basicMaterialStorageLifeObj->dump($inv_filter, 'balance_num');
                $account_num        = intval($storage_life_num['balance_num']);
                
                #防重复_累加数量
                $bmsl_id    = $ex_val['bmsl_id'];
                $storage_life_list[$bm_id][$bmsl_id]    = array(
                                                                    'branch_id' => $branch_id,
                                                                    'bm_id' => $bm_id,
                                                                    'expire_bn' => $ex_val['expire_bn'],
                                                                    'account_num' => $account_num,
                                                                );
                $storage_life_list[$bm_id][$bmsl_id]['in_num']    += intval($ex_val['in_num']);
                
                $storage_life_info[$ex_key]['account_num']    = $account_num;
            }
            
            #保存账面数量_以供参考
            $oInventory_object->update(array('storage_life_info'=>$storage_life_info), array('obj_id'=>$val['obj_id']));
        }
        
        #更新保质期库存
        $expire_updata    = array();
        if(empty($storage_life_list))
        {
            return false;
        }
        foreach ($storage_life_list as $bmKey => $bmItems)
        {
            foreach ($bmItems as $bmsl_id => $val)
            {
                $diff_num    = $val['in_num'] - $val['account_num'];
                
                #保质期批次号_盘盈
                if($diff_num > 0)
                {
                    $expire_updata[]	= array(
                                            'branch_id' => $branch_id,
                                            'bm_id' => $val['bm_id'],
                                            'expire_bn' => $val['expire_bn'],
                                            'difference_num' => $diff_num,
                                            'bill_id' => $inventory_id,#盘点ID
                                            'bill_bn' => $inventory['inventory_bn'],#盘点单编号
                                            'bill_type' => 60,#盘盈类型
                                            'bill_io_type' => 1,#入库
                                        );
                }
                #保质期批次号_盘亏
                elseif($diff_num < 0)
                {
                    $expire_updata[]	= array(
                                            'branch_id' => $branch_id,
                                            'bm_id' => $val['bm_id'],
                                            'expire_bn' => $val['expire_bn'],
                                            'difference_num' => abs($diff_num),
                                            'bill_id' => $inventory_id,#盘点ID
                                            'bill_bn' => $inventory['inventory_bn'],#盘点单编号
                                            'bill_type' => 6,#盘亏类型
                                            'bill_io_type' => 0,#出库
                                        );
                }
            }
        }
        
        $result    = $basicMReceiptStorageLifeLib->update($expire_updata, $msg);
        
        return $result;
    }

    /**
     * 获取保质期批次信息
     * @param  Array  $params 
     * @return Array
     */
    public function get_expire_bn_info($params, &$code, &$sub_msg)
    {
        $inventory_id = $params['inventory_id'];
        $barcode      = trim($params['barcode']);
        $branch_id    = $params['branch_id'];
        $selecttype   = $params['selecttype'];

        if (empty($barcode) || empty($branch_id) || empty($selecttype)) {
            $sub_msg = '必填参数不能为空';
            return false;
        }

        // 获取基础物料
        $ivObj = app::get('taoguaninventory')->model('inventory');
        if($selecttype == 'barcode')
        {
            $data = $ivObj->getProductbybarcode($branch_id, $barcode);
        }
        else if($selecttype == 'bn')
        {
            $data = $ivObj->getProductbybn($branch_id, $barcode);
        }
        if (empty($data))
        {
            $sub_msg = '没有找到相关记录';
            return false;
        }

        $bm_id = $data['bm_id'];

        #盘点保质期明细
        $oInventory   = app::get('taoguaninventory')->model('inventory');
        $inventory    = $oInventory->dump($inventory_id, 'inventory_type');
        
        $filter    = array('inventory_id'=>$inventory_id, 'product_id'=>$bm_id);
        if($inventory['inventory_type'] == '2')
        {
            $filter['pos_id|than'] = 0;#全盘_货位模式
        }

        $oinventory_object  = app::get('taoguaninventory')->model('inventory_object');
        $objItemlist        = $oinventory_object->getlist('obj_id, item_id, storage_life_info', $filter);
        if($objItemlist)
        {
            $storage_life_list    = array();
            foreach ($objItemlist as $key => $val)
            {
                $storage_life_info    = unserialize($val['storage_life_info']);
                
                foreach ($storage_life_info as $key_j => $val_j)
                {
                    $storage_life_list[$val_j['bmsl_id']]    = $val_j['expire_bn'];
                }
            }
        }
        
        #高级搜索
        $search_expire_bn      = trim($params['search_expire_bn']);
        $expiring_date_from    = $params['expiring_date_from'];
        $expiring_date_to      = $params['expiring_date_to'];
        $production_date_from  = $params['production_date_from'];
        $production_date_to    = $params['production_date_to'];
        
        $filter    = array('bm_id'=>$bm_id, 'branch_id'=>$branch_id, 'status'=>1);
        
        if($search_expire_bn)
        {
            $filter['expire_bn|has']    = $search_expire_bn;
        }
        if($expiring_date_from && $expiring_date_to)
        {
            $expiring_date_from    = strtotime($expiring_date_from);
            $expiring_date_to    = strtotime($expiring_date_to) + 86399;
            
            if($expiring_date_to > $expiring_date_from)
            {
                $filter['expiring_date|between']    = array($expiring_date_from, $expiring_date_to);
            }
        }
        if($production_date_from && $production_date_to)
        {
            $production_date_from    = strtotime($production_date_from);
            $production_date_to    = strtotime($production_date_to) + 86399;
        
            if($production_date_to > $production_date_from)
            {
                $filter['production_date|between']    = array($production_date_from, $production_date_to);
            }
        }

        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit   = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 1000 : intval($params['page_size']);
        
        #保质期批次号列表
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        $storageLifeBatch    = $basicMaterialStorageLifeObj->getList('bmsl_id, material_bn, expire_bn, production_date, expiring_date, in_num, balance_num, branch_id', $filter, ($page_no - 1) * $limit, $limit, 'expiring_date desc');
        
        #已存在的保质期禁止选择
        if($storage_life_list)
        {
            foreach ($storageLifeBatch as $key => $val)
            {
                $val['is_exist']    = ($storage_life_list[$val['bmsl_id']] ? true : false);
                
                $storageLifeBatch[$key]    = $val;
            }
        }

        return $storageLifeBatch;

    }

    /**
     * 获取保质期批次信息
     * @param  Interge  $bm_id 
     * @return Array
     */
    public function get_expire_bn_info_by_bm($bm_id)
    {
        if (!$bm_id) {
            return false;
        }

        $is_use_expire = kernel::single('material_storagelife')->checkStorageLifeById($bm_id);
        if ($is_use_expire) {
            $filter = array(
                'bm_id' => $bm_id, 
                'status' => 1,
            );
            $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
            $storage_life_info = $basicMaterialStorageLifeObj->getList('bmsl_id, bm_id, branch_id, expire_bn, production_date, expiring_date, in_num, balance_num', $filter);

            if (!empty($storage_life_info)) {
                $storage_life = array();
                foreach ($storage_life_info as $key => $value) {
                    $storage_life[$value['branch_id']][] = $value;
                }
                return $storage_life;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * 获取关联的保质期列表
     * @param  Array  $params 
     * @return Array
     */
    function get_storage_life($params, &$code, &$sub_msg)
    {
        $inventory_id    = $params['inventory_id'];
        $item_id         = $params['item_id'];
        $bm_id           = $params['bm_id'];
        
        if(empty($inventory_id) || empty($bm_id))
        {
            $sub_msg = '盘点id、物料id不能为空';
            return false;
        }
        
        $oInventory           = app::get('taoguaninventory')->model('inventory');
        $oInventory_object    = app::get('taoguaninventory')->model('inventory_object');
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        
        $inventory    = $oInventory->dump($inventory_id, 'branch_id, confirm_status');

        $filter = array(
            'inventory_id' => $inventory_id, 
            'product_id' => $bm_id,
        );

        if (!empty($item_id)) {
            $filter['item_id'] = $item_id;
        }
        
        $inventoryList        = $oInventory_object->getList('obj_id, product_id, storage_life_info', $filter);
        if(empty($inventoryList))
        {
            $sub_msg = '没有保质期批次详细信息';
            return false;
        }
        
        $storage_life_list    = array();
        foreach ($inventoryList as $key => $val)
        {
            $storage_life_info    = unserialize($val['storage_life_info']);
            
            foreach ($storage_life_info as $key_j => $val_j)
            {
                #未确认盘点_实时读取保质期账面数量
                if($inventory['confirm_status'] != 2)
                {
                    $inv_filter     = array('branch_id'=>$inventory['branch_id'], 'product_id'=>$val['product_id'], 'expire_bn'=>$val_j['expire_bn']);
                    $storage_life_num   = $basicMaterialStorageLifeObj->dump($inv_filter, 'balance_num');
                    $val_j['account_num']    = $storage_life_num['balance_num'];
                }
                
                $storage_life_list[]    = $val_j;
            }
        }
        
        return $storage_life_list;
        
    }
}
