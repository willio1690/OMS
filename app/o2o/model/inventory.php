<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_inventory extends dbeav_model{

    var $export_name = '门店盘点表';
    
    //csv字段标题定义
    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'export':
                $this->oSchema['csv'][$filter] = array(
                '*:物料名称' => 'material_name',
                '*:物料编码' => 'material_bn',
                '*:规格' => 'spec_info',
                '*:线上账面数' => 'accounts_num',
                '*:共享账面数' => 'accounts_share_num',
                );
                break;
            case 'branch':
                $this->oSchema['csv'][$filter] = array(
                '*:门店名称' => 'store_name',
                '*:门店编码' => 'store_bn',
                '*:盘点类型' => 'type',
                '*:盘点申请人' => 'op_name',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
    }
    
    //csv导出
    function fgetlist_csv(&$data, $filter, $offset){
        $post = $filter;
        if ($post["selected_store_bn"] && $post["selected_store_bn"] != "_NULL_"){
            //导出的完整数据数组
            $data['content']['main'] = array();
            //门店仓信息
            $mdlO2oStore = app::get('o2o')->model('store');
            $store_info = $mdlO2oStore->dump(array("store_bn"=>$post["selected_store_bn"]));
            $data['content']['branch']['store_name'] = $store_info['name'];
            $data['content']['branch']['store_bn'] = $store_info['store_bn'];
            $data['content']['branch']['type'] = $this->get_inventory_type($post['inventory_type'],'key');
            $data['content']['branch']['op_name'] = ''; //导出模板后自行填写
            
            //导出数据第一行，门店仓相关信息标题
            $title = array();
            foreach( $this->io_title('branch') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['content']['main'][] = '"'.implode('","',$title).'"';
    
            //门店仓相关信息数据行
            foreach( $this->oSchema['csv']['branch'] as $k => $v ){
                $branchRow[$v] = $this->charset->utf2local( utils::apath( $data['content']['branch'],explode('/',$v) ) );
            }
            $data['content']['main'][] = '"'.implode('","',$branchRow).'"';
    
            //盘点明细内容的标题行
            $title = array();
            foreach( $this->io_title('export') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['content']['main'][] = '"'.implode('","',$title).'"';
    
            if($post['inventory_type'] == 2){
                //选择了全盘类型 显示所有与此门店相关的物料明细信息内容
                $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
                $rs_product = $mdlO2oBranchProduct->getList("*",array("branch_id"=>$store_info["branch_id"]));
                if(!empty($rs_product)){
                    //先整体获取所有的bm_id
                    $bm_ids = array();
                    foreach ($rs_product as $var_product){
                        $bm_ids[] = $var_product["bm_id"];
                    }
                    //统一获取material_name和material_bn 与bm_id的关系
                    $mdlMaterialBasic = app::get('material')->model('basic_material');
                    $rs_material = $mdlMaterialBasic->getList("bm_id,material_bn,material_name",array("bm_id|in"=>$bm_ids));
                    $rl_bm_id_material_info = array();
                    foreach ($rs_material as $var_material){
                        $rl_bm_id_material_info[$var_material["bm_id"]] = array(
                            "material_bn" => $var_material["material_bn"],
                            "material_name" => $var_material["material_name"]
                        );
                    }
                    //统一获取spec_info规格 与bm_id的关系
                    $mdlMaterialBasicExt = app::get('material')->model('basic_material_ext');
                    $rs_material_ext = $mdlMaterialBasicExt->getList("bm_id,specifications",array("bm_id|in"=>$bm_ids));
                    $rl_bm_id_spec_info = array();
                    foreach ($rs_material_ext as $var_material_ext){
                        $rl_bm_id_spec_info[$var_material_ext["bm_id"]] = $var_material_ext["specifications"];
                    }
                    //统一获取accounts_num线上账面数和accounts_share_num共享账面数 与bm_id的关系
                    $mdlO2oInventoryItems = app::get('o2o')->model('inventory_items');
                    $rs_inventory_items = $mdlO2oInventoryItems->getList("bm_id,accounts_num,accounts_share_num",array("bm_id|in"=>$bm_ids));
                    if(!empty($rs_inventory_items)){
                        $rl_bm_id_inventory_items = array();
                        foreach ($rs_inventory_items as $var_inventory_item){
                            $rl_bm_id_inventory_items[$var_inventory_item["bm_id"]] = array(
                                "accounts_num" => $var_inventory_item["accounts_num"],
                                "accounts_share_num" => $var_inventory_item["accounts_share_num"],
                            );
                        }
                    }
                    //组明细内容
                    foreach ($rs_product as $f_var){
                        $row = array(
                            "material_name" => $rl_bm_id_material_info[$f_var["bm_id"]]["material_name"],
                            "material_bn" => $rl_bm_id_material_info[$f_var["bm_id"]]["material_bn"],
                            "spec_info" => $rl_bm_id_spec_info[$f_var["bm_id"]],
                            "accounts_num" => $rl_bm_id_inventory_items[$f_var["bm_id"]]["accounts_num"],
                            "accounts_share_num" => $rl_bm_id_inventory_items[$f_var["bm_id"]]["accounts_share_num"],
                        );
                        
                        foreach( $this->oSchema['csv']['export'] as $k => $v ){
                            if ($v){
                                $pRow[$v] = $this->utf8togbk( utils::apath( $row,explode('/',$v) ) );
                            }else{
                                $pRow[$v] = '';
                            }
                        }
                        $data['content']['main'][] = '"'.implode('","',$pRow).'"';
                    }
                }
            }
            
            return true;
        
        }
    }
    
    function utf8togbk($s){
        return iconv("UTF-8", "GBK//TRANSLIT", $s);
    }
    
    //准备导入
    function prepared_import_csv(){
        set_time_limit(0);
        $this->ioObj->cacheTime = time();
        $this->kvdata = '';
    }
    
    //准备导入 行
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        if (empty($row)){
            //最后一次$row就是空的 这里做错误信息处理
            //先判如果盘点类型是期初或者是部分盘的时候 csv填写的物料明细不能为空
            $f_kvdata = $this->kvdata;
            if ($this->flag){
                if ($this->not_exist_product_bn){
                    $temp = $this->not_exist_product_bn;
                    $error_text = '\n数据库中不存在的商品货号：';
                    $msg['error'] = $this->get_import_error_msg($temp,$error_text,$msg['error']);
                }
                if ($this->not_exist_account_numbers){
                    $temp = $this->not_exist_account_numbers;
                    $error_text = '\n相应物料的线上账面数或者共享账面数不能为空：';
                    $msg['error'] = $this->get_import_error_msg($temp,$error_text,$msg['error']);
                }
                if ($this->not_exist_rl_branch_product){
                    $temp = $this->not_exist_rl_branch_product;
                    $error_text = '\n此门店仓和物料不存在供货关系：';
                    $msg['error'] = $this->get_import_error_msg($temp,$error_text,$msg['error']);
                }
                $this->kvdata = '';
                return false;
            }
            if(in_array($f_kvdata["branch"]["contents"][0][2], array(1,3))){
                //盘点类型是期初或部分盘 物料明细必须有值
                if(!$f_kvdata["products"]["contents"]){
                    $msg['error'] .= '\n盘点类型是期初或部分盘时必须填写下面的物料信息。';
                    $this->kvdata = '';
                    return false;
                }
            }
            //通过错误处理检查后返回true
            return true;
        }
    
        $mark = false;
        $fileData = $this->kvdata;
    
        if( !$fileData ) $fileData = array();
    
        if( substr($row[0],0,2) == '*:' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            return $titleRs;
        }else{
            $basicMaterialObj = app::get('material')->model('basic_material');
            $mdlOmeBranch = app::get('ome')->model('branch');
            $mdlO2oInventory = app::get('o2o')->model('inventory');
            $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
            if( $row[0] ){
                if( array_key_exists( '*:物料名称',$title ) ){
                    //盘点货品处理
                    //先判断 线上账面数或者共享账面数不能为空
                    if($row[3] == "" || $row[4] == ""){
                        $this->flag = true;
                        $this->not_exist_account_numbers = isset($this->not_exist_account_numbers)?array_merge($this->not_exist_account_numbers,array($row[1])):array($row[1]);
                    }else{
                        //判断是否存在该物料
                        $product = $basicMaterialObj->dump(array('material_bn'=>trim($row[1])), 'bm_id');
                        if(!$product){
                            $this->flag = true;
                            $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[1])):array($row[1]);
                        }else{
                            //判断物料是否与门店仓有关联关系
                            $rs_o2o_rl = $mdlO2oBranchProduct->dump(array("branch_id"=>$this->import_branch_id,"bm_id"=>$product["bm_id"]),'id');
                            if(!$rs_o2o_rl){
                                $this->flag = true;
                                $this->not_exist_rl_branch_product = isset($this->not_exist_rl_branch_product)?array_merge($this->not_exist_rl_branch_product,array($row[1])):array($row[1]);
                            }
                            $row['product_id'] = $product['bm_id'];
                        }
                        unset($product);
                        $fileData['products']['contents'][] = $row;
                    }
                }else {
                    //盘点门店仓处理
                    $branch = $mdlOmeBranch->dump(array("branch_bn"=>$row["1"],"b_type"=>2),"branch_id,name");
                    if (!$branch){
                        $msg['error'] = "没有此门点仓：".$row[0];
                        unset($branch);
                        return false;
                    }
                    $branch_id = $branch['branch_id'];
                    $this->import_branch_id = $branch_id;
                    $fileData['branch']['branch_id'] = $branch_id;
                    $fileData['branch']['name'] = $branch['name'];
                    $inventory_type = $this->get_inventory_type($row[2],'value');
                    if(!$inventory_type){
                        $msg['error'] = "盘点类型无法标识";
                        return false;
                    }
                    if($inventory_type == "2"){
                        //全盘 判断是否有此门店仓未确认的全盘和部分盘的盘点单
                        $inv_exist2 = $mdlO2oInventory->dump(array("branch_id"=>$branch_id,"status"=>1,"inventory_type|in"=>array(2,3)),"inventory_id");
                        if($inv_exist2){
                            $msg['error'] = "此门店仓已有盘点方式为全盘或部分的盘点单存在,请确认后再导入";
                            unset($inv_exist2);
                            return false;
                        }
                    }else if($inventory_type == "3"){
                        //部分盘 判断是否有此门店仓未确认的全盘单
                        $inv_exist3 = $mdlO2oInventory->dump(array("branch_id"=>$branch_id,"status"=>1,"inventory_type"=>2),'inventory_id');
                        if($inv_exist3){
                            $msg['error'] = "请将此门店仓全盘确认后再新建部分盘点";
                            unset($inv_exist3);
                            return false;
                        }
                    }else if($inventory_type == "1"){
                        //期初
                        $branch_product = kernel::single('o2o_inventorylist')->check_product_iostock($branch_id);
                        if($branch_product){
                            $msg['error'] = "此门店仓已存在库存记录不可以期初盘点";
                            unset($branch_product);
                            return false;
                        }
                        $branch_inventory = kernel::single('o2o_inventorylist')->get_inventorybybranch_id($branch_id);
                        if($branch_inventory){
                            $msg['error'] = "此门店仓已有盘点单存在!";
                            unset($branch_inventory);
                            return false;
                        }
                    }
                    $row[2] = $inventory_type;
                    $fileData['branch']['contents'][] = $row;
                }
                $this->kvdata = $fileData;
            }else {
                $msg['error'] = "物料名称不能为空！";
                return false;
            }
        }
        
        return null;
    }
    
    //导入 获取数据 加入队列
    function finish_import_csv(){
        $data = $this->kvdata; unset($this->kvdata);
        
        $oQueue = app::get('base')->model('queue');
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        
        //每50个物料记录 一个队列
        $number = $page = 0;   $limit = 50;
        
        //获取盘点申请人的ID
        $op_id = 1;
        $mdlDesktopUser = app::get('desktop')->model('users');
        $user_name = trim($data['branch']['contents'][0][3]);
        if($user_name){
            $rs_user = $mdlDesktopUser->dump(array("name"=>$user_name),"user_id");
            if(!empty($rs_user)){
                $op_id = $rs_user["user_id"];
            }
        }
        
        //盘点主表
        $inv = array(
             "inventory_bn" => $this->get_inventory_bn(),
             "inventory_type" => $data['branch']['contents'][0][2],
             "op_id" => $op_id,
             "createtime" => time(),
             "branch_id" => $data['branch']['branch_id'],
        );
        $re = $mdlO2oInventory->save($inv);
        if($inv["inventory_type"] == "2"){
            //全盘获取有库存的物料记录并补全 漏填物料记录线上账面数和共享账面数为0
            $material_list = $this->get_import_material_list($data['branch']['branch_id'],$data['products']['contents']);
        }else{
            //期初 部分盘 拿csv填写的物料信息
            $material_list = $data['products']['contents'];
        }
        
        $psdf['branch_id'] = $data['branch']['branch_id'];
        $psdf['inv_id']  = $inv['inventory_id'];
        
        $sdfs = array();
        foreach ($material_list as $k => $v){
            $sdf = array(
                "accounts_num" => (int)trim($v[3]),
                "accounts_share_num" => (int)trim($v[4]),
                "bm_id" => $v["product_id"],
            );
            if ($number < $limit){
                $number++;
            }else{
                $page++;
                $number = 0;
            }
            $sdfs[$page][] = $sdf;
        }
        
        unset($data, $inv, $material_list);#销毁
        
        foreach ($sdfs as $i){
            $psdf['products']  = $i;
            $queueData = array(
                    'queue_title'=>'门店盘点导入',
                    'start_time'=>time(),
                    'params'=>array(
                            'sdfdata'=>$psdf,
                            'app' => 'o2o',
                            'mdl' => 'inventory'
                    ),
                    'worker'=>'o2o_inventory_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();

        return null;
    }
    
    //导入时组合错误信息
    private function get_import_error_msg($temp,$error_test,&$error_msg){
        $tmp = array_unique($temp); sort($tmp);
        $error_msg .= $error_test;
        $ms = ''; $tmp1 = array(); $tmp2 = array();
        foreach ($tmp as $k => $v){
            if ($k >= 10){
                $ms = '...\n'; break;
            }
            if ($k < 5){
                $tmp1[] = $v; continue;
            }
            $tmp2[] = $v;
        }
        $error_msg .= '\n'.implode(',', $tmp1);
        if (!empty($tmp2)) $error_msg .= '\n'.implode(',', $tmp2);
        $error_msg .= $ms;
        return $error_msg;
    }
    
    //全盘导入时获取物料信息
    private function get_import_material_list($branch_id,$products_contents){
        $material_list = array();
        $import_bm_ids = array();
        foreach ($products_contents as $var_product){
            $import_bm_ids[] = $var_product["product_id"];
            $material_list[] = $var_product;
        }
        //如有遗漏的物料信息 则补全
        $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
        $rs_product = $mdlO2oBranchProduct->getList("bm_id",array("branch_id"=>$branch_id,"bm_id|notin"=>$import_bm_ids));
        if(!empty($rs_product)){
            foreach ($rs_product as $var_p){
                $temp_arr = array(
                    "3" => 0, //线上账面数
                    "4" => 0, //共享账面数
                    "product_id" => $var_p["bm_id"], 
                );
                $material_list[] = $temp_arr;
            }
        }
        return $material_list;
    }
    
    
    //$search是'key'时$inventory_type传1/2/3来获取$type的value  如$search是默认value时$inventory_type传期初/全盘/部分盘 来获取$type的key
    function get_inventory_type($inventory_type,$search="value"){
        $type = array (
                    '1' => '期初',
                    '2' => '全盘',
                    '3' => '部分盘',
                );
        if($search=='key'){
            return $type[$inventory_type];
        }else{
            $result = array_search($inventory_type,$type);
            return $result;
        }
    }
    
    //生成盘点单号
    function get_inventory_bn(){
        $head = "MDPD";
        $rand4 = rand(1,9999);
        $current_date = date('ymdHis');
        $inventory_bn = "MDPD".$current_date.str_pad($rand4,4,'0',STR_PAD_LEFT);
        return $inventory_bn;
    }
    
    //盘点类型
    function modifier_inventory_type($row){
        switch ($row){
            case "1":
                $inventory_type = "期初";
                break;
            case "2":
                $inventory_type = "全盘";
                break;
            case "3":
                $inventory_type = "部分盘";
                break;
        }
        return $inventory_type;
    }
    
    //盘点时间
    function modifier_confirm_time($row){
        if(!$row){
            return "-";
        }else{
            return date("Y-m-d H:i:s",$row);
        }
    }
    
    //状态
    function modifier_status($row){
        switch ($row){
            case "1":
                $status = "未确认";
                break;
            case "2":
                $status = "已确认";
                break;
            case "3":
                $status = "作废";
                break;
        }
        return $status;
    }
    
    
    //扩展字段先定义
    function extra_cols(){
        return array(
            'column_store_name' => array('label'=>'门店名称','width'=>'150','func_suffix'=>'store_name',"order"=>"5"),
            'column_op_name' => array('label'=>'申请人','width'=>'80','func_suffix'=>'op_name',"order"=>"15"),
            'column_confirm_op_name' => array('label'=>'盘点人','width'=>'80','func_suffix'=>'confirm_op_name',"order"=>"20"),
        );
    }
    
    function extra_store_name($rows){
        return kernel::single('o2o_extracolumn_inventory_storename')->process($rows);
    }
    
    function extra_op_name($rows){
        return kernel::single('o2o_extracolumn_inventory_opname')->process($rows);
    }
    
    function extra_confirm_op_name($rows){
        return kernel::single('o2o_extracolumn_inventory_confirmopname')->process($rows);
    }
    
}