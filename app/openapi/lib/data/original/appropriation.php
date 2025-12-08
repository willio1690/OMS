<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_appropriation{

    public function add($data){
        $result = array('rsp'=>'succ','msg' => '调拨单创建成功');
        $oAppropriation = app::get('taoguanallocate')->model('appropriation');
        $oAppropriation_items = app::get('taoguanallocate')->model('appropriation_items');
        $appropriationObj = kernel::single('taoguanallocate_appropriation');
        $channelLib = kernel::single('channel_func');
        $oStock = kernel::single('console_stock_products');
        $appropriation_type = intval($data['appropriation_type']);
        $from_branch = $data['from_branch'];
        $to_branch = $data['to_branch'];
        $logi_name = $data['logi_name'];
        $operator = $data['operator']?$data['operator']:'未知';
        $is_check = $data['is_check'];
        $memo = $data['memo'];     
        $items = json_decode($data['items'],true);
        if ($appropriation_type == '' || $from_branch == '' || $to_branch == '' || $items == '') {
            $result['rsp'] = 'fail';
            $result['msg'] = '必填字段不可为空!';
            return $result;
        }
        //判断调出仓库是否存在
        if(!$from_branch_ids = $this->_getBranchBybn($from_branch)){
            $result['rsp'] = 'fail';
            $result['msg'] = '调出仓库不存在!';
            return $result;
        }
        $from_branch_id = $from_branch_ids['branch_id'];
        //判断调入仓库是否存在
        if(!$to_branch_ids = $this->_getBranchBybn($to_branch)){
            $result['rsp'] = 'fail';
            $result['msg'] = '调入仓库不存在!';
            return $result;
        }
        $to_branch_id = $to_branch_ids['branch_id'];
        //判断调拨类型
        if($appropriation_type == 1){
            $type = 1;
            //判断仓库类型
            if(!$channelLib->isSelfWms($from_branch_ids['wms_id'])||!$channelLib->isSelfWms($to_branch_ids['wms_id'])){
                $result['rsp'] = 'fail';
                $result['msg'] = '调拨类型为直接调拨时，仓库类型必须为自有仓库!';
                return $result;
            }           
        }elseif($appropriation_type == 2){
            $type = 2;
        }else{
            $result['rsp'] = 'fail';
            $result['msg'] = '调拨类型不存在!';
            return $result;           
        }
        if(!is_array($items)){
            $result['rsp'] = 'fail';
            $result['msg'] = 'items参数必须为数组!';
            return $result; 
        }
        //判断物流公司
        if(!empty($logi_name)){
            if(!$corp_ids=  $this->_getCorpByName($logi_name)){
                $result['rsp'] = 'fail';
                $result['msg'] = '物流公司不存在!';
                return $result; 
            }
        }
        $corp_id = $corp_ids['corp_id'];
        foreach ($items as $item) {
            if(!$item['bn'] || !$item['nums']){
                $result['rsp'] = 'fail';
                $result['msg'] = 'items必填字段不可为空!';
                return $result;
            }
            if(!$bproduct = $this->_getProductByBnId($item['bn'],$from_branch_id)){                
                $result['rsp'] = 'fail';
                $result['msg'] = '在'.$from_branch.'仓库不存在'.'货号'.$item['bn'].'!';
                return $result;
            }
            if (!is_numeric($item['nums']) || $item['nums'] < 1){
                $result['rsp'] = 'fail';
                $result['msg'] = '货号'.$item['bn'].'的退货数量必须为数字且大于0!';
                return $result;
            }         
            if($item['nums'] > $bproduct['store']){
                $result['rsp'] = 'fail';
                $result['msg'] = '货号'.$item['bn'].'的调拨数量不可大于库存数量!';
                return $result;
            }            
            unset($item);
        }
        $appropriation_no = $appropriationObj->gen_appropriation_no();
        $appro_data = array(
            'appropriation_no'=> $appropriation_no,
            'type' => 0,
            'create_time' => time(),
            'operator_name' => $operator,
            'memo' => $memo,
            'corp_id' => $corp_id,
        );
        $oAppropriation->save($appro_data);             
        $appropriation_id = $appro_data['appropriation_id'];
        foreach ($items as $item) {
            $product = $this->_getProductByBn($item['bn']);
            $from_branch_num = $oStock->get_branch_usable_store($from_branch_id,$product['product_id']);
            $to_branch_num = $oStock->get_branch_usable_store($to_branch_id,$product['product_id']);
            $item_data = array(
                'appropriation_id'=>$appropriation_id,
                'bn'=>$item['bn'],
                'product_name'=>$product['name'],
                'product_id'=>$product['product_id'],
                'from_branch_id'=>$from_branch_id==''? 0:$from_branch_id,
                'from_pos_id'=>$from_pos_id=='' ? 0:$from_pos_id,
                'to_branch_id'=>$to_branch_id=='' ? 0:$to_branch_id,
                'to_pos_id'=>$to_pos_id=='' ? 0:$to_pos_id,
                'num'=>$item['nums'],
                'from_branch_num'=>$from_branch_num,
                'to_branch_num'=>$to_branch_num,
            );
            $oAppropriation_items->save($item_data);
        }
        if($type == 1){//直接调拨
            if($this->do_iostock($appropriation_id,$msg)){
                return $result;
            }  else {
                $result['rsp'] = 'fail';
                $result['msg'] = $msg;
                return $result;
            }
        }else{//出入库调拨，先生成出库单，出库单确认后生成入库单,出入库单确认后，生成出入库明细
            $iso_id = $this->do_out_iostockorder($appropriation_id,$msg);
            if($iso_id){
                if($is_check=='是'){
                    $this->doCheck($iso_id, $msg);
                    $result['rsp'] = 'succ';
                    $result['msg'] = $msg;
                    return $result;
                }
                return $result;
            }  else {
                $result['rsp'] = 'fail';
                $result['msg'] = $msg;
                return $result;
            }
        } 
    }
    
    function getList($start_time,$end_time,$appropriation_no='',$offset=0,$limit=100){
        
        if(empty($start_time) || empty($end_time)){
            return false;
        }
        
        $iostockorderIsoObj = app::get('taoguaniostockorder')->model('iso');
        $tglAptObj = app::get('taoguanallocate')->model('appropriation');
        $tglAptItemsObj = app::get('taoguanallocate')->model('appropriation_items');
        $branchObj = app::get('ome')->model('branch');
        $iostockObj = app::get('ome')->model('iostock');
        //基础物料获取barcode
        $basicMaterialBarcode = kernel::single('material_basic_material_barcode');
        
        //2016.1月之前没有调拨单号关联出入库明细字段appropriation_no 所以is not null过滤掉之前无关联的调拨单号
        $where = " where appropriation_no is not null and create_time >=".$start_time." and create_time <".$end_time;
        $group_by = " group by appropriation_no";
        $order_by = " order by create_time asc";
        if($appropriation_no){
            $where .= " and appropriation_no = '".$appropriation_no."'";
        }
        $sqlstr = "select appropriation_no from ".kernel::database()->prefix."taoguaniostockorder_iso ".$where.$group_by.$order_by." limit ".$offset.",".$limit;
        $appt_info = $iostockorderIsoObj->db->select($sqlstr);
        
        if($appt_info){
            $count = count($appt_info);
            
            $appt_nos = array();
            foreach ($appt_info as $var_appt_info){
                $appt_nos[] = $var_appt_info["appropriation_no"];
            }
            
            //获取拨单号相关货品信息
            $rs_appt_info = $tglAptObj->getList('appropriation_id,appropriation_no',array('appropriation_no|in' => $appt_nos));
            $appt_id_arr = array();
            $rl_appt_id_appt_no = array();
            foreach ($rs_appt_info as $var_appt_info){
                $appt_id_arr[] = $var_appt_info["appropriation_id"];
                $rl_appt_id_appt_no[$var_appt_info["appropriation_id"]] = $var_appt_info["appropriation_no"];
            }
            $rs_appt_items_info = $tglAptItemsObj->getList('appropriation_id,product_id,bn,product_name,num',array('appropriation_id|in' => $appt_id_arr));
            
            //获取调拨单号关联product信息
            $rl_appt_no_product_info = array();
            $products_count = 0;
            foreach ($rs_appt_items_info as $var_appt_item){
                //获取当前基础物料的barcode
                $product_barcode = $basicMaterialBarcode->getBarcodeById($var_appt_item['product_id']);
                $temp_product_arr = array(
                        "bn" => $var_appt_item["bn"],
                        "name" => $var_appt_item["product_name"],
                        "barcode" => $product_barcode,
                        "nums" => $var_appt_item["num"],
                );
                $rl_appt_no_product_info[$rl_appt_id_appt_no[$var_appt_item["appropriation_id"]]][] = $temp_product_arr;
                $products_count += intval($var_appt_item["num"]);
            }
            
            //获取调拨单号相关的iso_bn关联ome_iostock表的original_bn
            $iso_bn_info = $iostockorderIsoObj->getList('iso_bn,appropriation_no', array('appropriation_no|in' => $appt_nos),0,-1,"create_time asc");
            $iso_bn_arr = array();
            $rl_appt_no_iso_bn = array();
            foreach ($iso_bn_info as $var_iso_bn_info){
                $iso_bn_arr[] = $var_iso_bn_info["iso_bn"];
                $rl_appt_no_iso_bn[$var_iso_bn_info["appropriation_no"]][] = $var_iso_bn_info["iso_bn"];
            }
            //相关调拨的出入库类型
            $iostocktypeInfos = array("4" => "调拨入库","40" => "调拨出库","11" => "调拨入库取消");
            //获取仓库信息
            $branchInfos = array();
            $branch_arr = $branchObj->getList('branch_id,branch_bn,name', array(), 0, -1);
            foreach ($branch_arr as $k => $branch){
                $branchInfos[$branch['branch_id']] = array('branch_bn'=>$branch['branch_bn'],'name'=>$branch['name']);
            }
            //获取appt_no相关出入库明细
            $iostock_fields = "iostock_id,iostock_bn,branch_id,type_id,create_time,original_bn,memo,iostock_price,unit_cost";
            $iostock_info = $iostockObj->getList($iostock_fields,array("original_bn|in"=>$iso_bn_arr),0,-1,"create_time asc");
            $rl_original_bn_iosotck = array();
            foreach ($iostock_info as $var_iostock_info){
                $temp_iostock_arr = array(
                        "iostock_id" => $var_iostock_info["iostock_id"],
                        "iostock_bn" => $var_iostock_info["iostock_bn"],
                        "branch_bn" => $branchInfos[$var_iostock_info['branch_id']]['branch_bn'],
                        "branch_name" => $branchInfos[$var_iostock_info['branch_id']]['name'],
                        "type" => $iostocktypeInfos[$var_iostock_info["type_id"]],
                        "iostock_time" => date('Y-m-d H:i:s',$var_iostock_info['create_time']),
                        "memo" => $var_iostock_info["memo"],
                        "original_bn" => $var_iostock_info["original_bn"],
                        "iostock_price" => $var_iostock_info["iostock_price"],
                        "unit_cost" => $var_iostock_info["unit_cost"],
                );
                $rl_original_bn_iosotck[$var_iostock_info["original_bn"]] = $temp_iostock_arr;
            }
            $rl_appt_no_iostock = array();
            foreach ($rl_appt_no_iso_bn as $key_appt_no => $iso_bns){
                foreach ($iso_bns as $var_iso_bn){
                    if($rl_original_bn_iosotck[$var_iso_bn]){
                        $rl_appt_no_iostock[$key_appt_no][] = $rl_original_bn_iosotck[$var_iso_bn];
                    }
                }
            }
            
            //组合最终的lists
            $lists = $appt_info;
            foreach ($lists as &$var_appt_no){
                $var_appt_no["products"] = $rl_appt_no_product_info[$var_appt_no["appropriation_no"]];
                $var_appt_no["iostock"] = $rl_appt_no_iostock[$var_appt_no["appropriation_no"]];
            }
            unset($var_appt_no);
            
            return array(
                    'count' => $count,
                    'products_count' => $products_count,
                    'lists' => $lists
            );
            
        }else{
            
            return array(
                    'count' => 0,
                    'products_count' => 0,
                    'lists' => array()
            );
            
        }
        
        
        
        
    }
    
    //通过仓库名称获取仓库的id
    private function _getBranchByname($branch_name) {
        $branchModel = app::get('ome')->model('branch');
        $branch = $branchModel->dump(array( 'name' => $branch_name),'branch_id,wms_id');
        return $branch;
    }
    //通过仓库编码获取仓库信息
    private function _getBranchBybn($branch_bn) {
        $branchModel = app::get('ome')->model('branch');
        $branch = $branchModel->dump(array( 'branch_bn' => $branch_bn),'branch_id,wms_id');
        return $branch;
    }
    //通过货号和仓库id判断这个仓库里面是否存在这个货品和获取货品的id
    private function _getProductByBnId($bn,$branch_id) {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $branchModel = app::get('ome')->model('branch_product');
        $product = $basicMaterialObj->dump(array( 'material_bn' => $bn),'bm_id');
        $bproduct = $branchModel->dump(array('branch_id'=> $branch_id,'product_id'=>$product['bm_id']));
        return $bproduct;
    }
    //通过货号获取货品的信息
    private function _getProductByBn($bn) {
        $basicMaterialObj    = app::get('material')->model('basic_material');
        $bMaterialRow        = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id, material_name, material_bn');
        $product             = array('product_id'=>$bMaterialRow['bm_id'], 'bn'=>$bMaterialRow['material_bn'], 'name'=>$bMaterialRow['material_name']);
        
        return $product;
    }
    //通过货号获取货品的信息
    private function _getCorpByName($name) {
        $corpModel = app::get('ome')->model('dly_corp');
        $corp = $corpModel->dump(array( 'name' => $name),'corp_id');
        return $corp;
    }
    
    /**
   * 
   * 调拔单出库
   * @param  appropriation_id
   * @param  $msg
   */
   function do_out_iostockorder($appropriation_id,&$msg){
       #判断是否开启固定成本，如果开启，price等于商品成本价
       $cost = false;
       if(app::get('tgstockcost')->is_installed()){
           $tgstockcost = app::get("ome")->getConf("tgstockcost.cost");
           if($tgstockcost == 2){
               $cost= true;
           }
       }
        $iostock_instance = kernel::single('siso_receipt_iostock');   
        $appitemObj = app::get('taoguanallocate')->model('appropriation_items');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $products = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguanallocate_appropriation` WHERE `appropriation_id`=\''.$appropriation_id.'\'';
        $app_detail = $db->selectrow($sql);
        $app_items_detail = $appitemObj->getList('*', array('appropriation_id'=>$appropriation_id), 0, -1);
        $branch_id = 0;
        $to_branch_id = 0;
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){
            	if(!$branch_id){
                    $branch_id = $v['from_branch_id'];
            	}

                if(!$to_branch_id){
                    $to_branch_id = $v['to_branch_id'];
                }

            	if($cost){
            	    #如果已经开启固定成本，则获取商品的成本价
            	    $product = $basicMaterialExtObj->dump(array('bm_id'=>$v['product_id']),'unit,cost');
            	}else{
            	    #如果没有开启，则不需要获取成本价
            	    $product = $basicMaterialExtObj->dump(array('bm_id'=>$v['product_id']),'unit');

            	    #调拨出库时，获取对应的单位成本
            	    $unit_cost = $db->selectRow('select unit_cost from  sdb_ome_branch_product where branch_id='.$branch_id.' and product_id='.$v['product_id']);
            	    $product['cost'] = $unit_cost['unit_cost'];
            	}
              
                $products[$v['product_id']] = array(
                    'unit'=>$product['unit'],
                    'name'=>$v['product_name'],
                    'bn'=>$v['bn'],
                    'nums'=>$v['num'],
                    'price'=>$product['cost']?$product['cost']:0
                );
            }
        }
        
        eval('$type='.get_class($iostock_instance).'::ALLOC_LIBRARY;');
        $data =array (
           'iostockorder_name' => date('Ymd').'出库单', 
           'supplier' => '', 
           'supplier_id' => 0, 
           'branch' => $branch_id,
           'extrabranch_id'=>$to_branch_id,
           'type_id' => $type, 
           'iso_price' => 0,
           'memo' => $app_detail['memo'], 
           'operator' => 'admin', 
           'original_bn'=>'',
           'original_id'=>$appropriation_id,
           'products'=>$products,
           'appropriation_no'=>$app_detail['appropriation_no']
       );
        if ($app_detail['corp_id']) {
            $data['corp_id'] = $app_detail['corp_id'];
        }

        $iostockorder_instance = kernel::single('console_iostockorder');
        return $iostockorder_instance->save_iostockorder($data,$msg);
    }

    /**
    * 
    * 生成调拨单出入库明细
    * @param  $appropriation_id 
    * @param 
    * @param  $msg
    */
    function do_iostock($appropriation_id,&$msg){
    	$allow_commit = false;
        kernel::database()->beginTransaction();

        $iostock_instance = kernel::service('ome.iostock');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($appropriation_id);
            $out = array();//调出
            $in = array();//调入
            //$oBranchProduct   = app::get('ome')->model('branch_product');
            foreach($iostock_data as $item_id=>$iostock){
            	$iostock['nums'] = abs($iostock['nums']);
            	
            	$iostock['branch_id'] = $iostock['from_branch_id'];
            	unset($iostock['from_branch_id']);
            	$out[$item_id] = $iostock;
            	
            	$iostock['branch_id'] = $iostock['to_branch_id'];
            	unset($iostock['to_branch_id']);
            	$in[$item_id] = $iostock;
            
            }
            if(count($out) > 0){
                 $stockoutLib = kernel::single('siso_receipt_iostock_stockout');
                 $stockoutLib->_typeId = 40;
                 if($stockoutLib->create($out, $data, $out_msg)){
                    $allow_commit = true;
                }
            	
            }
            if(count($in) > 0 && $allow_commit){
                $allow_commit = false;
                $stockinLib = kernel::single('siso_receipt_iostock_stockin');
                $stockinLib->_typeId =4;
                if($stockinLib->create($in, $data, $in_msg)){
                   $allow_commit = true;
                }

            }
            
        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            $msg['out_msg'] = $out_msg;
            $msg['in_msg'] = $in_msg;

            kernel::database()->rollBack();

            return false;
        }
        
    }

    /**
     * 组织出库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($appropriation_id){
        
        $appitemObj = app::get('taoguanallocate')->model('appropriation_items');
        
        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguanallocate_appropriation` WHERE `appropriation_id`=\''.$appropriation_id.'\'';
        $app_detail = $db->selectrow($sql);
        $app_items_detail = $appitemObj->getList('*', array('appropriation_id'=>$appropriation_id), 0, -1);
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){

                $bp_data = $db->selectrow('select unit_cost from sdb_ome_branch_product where branch_id = '.$v['from_branch_id'].' and product_id = '.$v['product_id']);
                
                $iostock_data[$v['item_id']] = array(
                    'from_branch_id' => $v['from_branch_id'],
                    'to_branch_id' => $v['to_branch_id'],
                    'original_bn' => '',
                    'original_id' => $appropriation_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $bp_data['unit_cost']?$bp_data['unit_cost']:0,
                    'nums' => $v['num'],
                    'oper' => $app_detail['operator_name'],
                    'create_time' => $app_detail['create_time'],
                    'operator' => 'admin',
                    'memo' => $app_detail['memo'],
                );
            }
        }
        return $iostock_data;
    }
    /**
     * 出库审核
     * @access public
     * @param String $iso_id 出入库ID
     * @return 
     */
    public function doCheck($iso_id,&$msg){
        #更新单据审核状态
        $iso_id = intval($iso_id);
        $io = '0';
        $pStockObj = kernel::single('console_stock_products');
        $isoObj = app::get('taoguaniostockorder')->model('iso');

        #库存状态判断\
        $iso = $isoObj->dump($iso_id,'check_status,branch_id,iso_bn');
        $branch_id = $iso['branch_id'];
        if ($iso['check_status']!='1'){
            $msg='出库单已审核!';
        }

        if ($io == '0'){
            $oIso_items = app::get('taoguaniostockorder')->model('iso_items');
            #需要判断可用库存是否足够
            $iso_items = $oIso_items->getlist('bn,nums,product_id',array('iso_id'=>$iso_id),0,-1);

            foreach($iso_items as $ik=>$iv){
                //判断选择商品库存是否充足
                $usable_store = $pStockObj->get_branch_usable_store($branch_id,$iv['product_id']);

                if($iv['nums'] > $usable_store){
                    $msg = '出库数量不可大于库存数量.'.$usable_store;
                }
            }

        }
        $iso_data = array('check_status'=>'2');
        $result = $isoObj->update($iso_data,array('iso_id'=>$iso_id));
        if ($result){
            if ($io == '0'){
                #将库存冻结
                kernel::single('console_receipt_stock')->clear_stockout_store_freeze(array('iso_bn'=>$iso['iso_bn']),'+');
                #出库
                kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>$iso_id),false);
            }else{
                #入库
                kernel::single('console_event_trigger_otherstockin')->create(array('iso_id'=>$iso_id),false);
            }

            $msg='调拨单创建成功，出库单审核成功!';
        }else{
            $msg='调拨单创建成功，出库单审核失败!';
        }

    }

}