<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_branch_product extends dbeav_model{

    //有o2o库存记录 或有 相关的发货单记录的 不能删除
    /**
     * pre_recycle
     * @param mixed $rows rows
     * @return mixed 返回值
     */
    public function pre_recycle($rows){
        
        $mdlO2oProductStore = app::get('o2o')->model('product_store');
        $mdlOmeDelivery = app::get('ome')->model('delivery');
        
        $flag_store = true;
        $flag_delivery = true;
        
        foreach ($rows as $var){
            $current_bm_id = $var["bm_id"];
            $current_branch_id = $var["branch_id"];
            //先判是否有o2o库存记录
            $rs_store = $mdlO2oProductStore->dump(array("branch_id"=>$current_branch_id,"bm_id"=>$current_bm_id),"id");
            if(!empty($rs_store)){
                $flag_store = false;
                break;
            }
            //再判是否有相关的发货单记录
            $sql = 'select od.delivery_id from sdb_ome_delivery as od left join sdb_ome_delivery_items as odi on od.delivery_id = odi.delivery_id  
                    where od.branch_id='.$current_branch_id.' and odi.product_id='.$current_bm_id.' limit 1';
            $rs_delivery = $mdlOmeDelivery->db->select($sql);
            if(!empty($rs_delivery)){
                $flag_delivery = false;
                break;
            }
        }
        
        if(!$flag_store || !$flag_delivery){
            //获取门店名和商品名
            $mdlMaterialBasic = app::get('material')->model('basic_material');
            $rs_bm_name = $mdlMaterialBasic->dump(array("bm_id"=>$current_bm_id),"material_name");
            $mdlOmeBranch = app::get('ome')->model('branch');
            $rs_store_name = $mdlOmeBranch->dump(array("branch_id"=>$current_branch_id),"name");
            if(!$flag_store){
                $this->recycle_msg = '门店'.$rs_store_name["name"].'有商品'.$rs_bm_name["material_name"].'的库存记录 ，无法进行删除！';
                return false;
            }
            if(!$flag_delivery){
                $this->recycle_msg = '发货单有门店'.$rs_store_name["name"].'和商品'.$rs_bm_name["material_name"].'的记录 ，无法进行删除！';
                return false;
            }
        }
        
        return true;
        
    }
    
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }
    
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        if($row){
            if( substr($row[0],0,1) == '*' ){
            }else{
                
                $re = base_kvstore::instance('o2o_branch_product')->fetch('o2obranchproduct-'.$this->ioObj->cacheTime,$fileData);
                if( !$re ) $fileData = array();
                
                if(trim($row[1])==''){
                    $msg['error']='门店编码不能为空!';
                    return false;
                }else if(trim($row[3])==''){
                    $msg['error']='基础物料编码不能为空!';
                    return false;
                }else{
                    $basicMaterialObj = app::get('material')->model('basic_material');
                    $omeBranchObj = app::get('ome')->model('branch');
                    
                    $product = $basicMaterialObj->getList('bm_id, material_bn', array('material_bn'=>$row[3]), 0, 1);
                    if(count($product) == '0'){
                        $msg['error']='基础物料编码在系统中不存在';
                        return false;
                    }
                    
                    $branch = $omeBranchObj->getList('branch_id, branch_bn', array('branch_bn'=>$row[1]), 0, 1);
                    if(count($branch) == '0'){
                        $msg['error']='门店编码在系统中不存在';
                        return false;
                    }
                    
                    //货品编码不能重复
                    $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
                    $info = $mdlO2oBranchProduct->getlist('id',array('branch_id'=>$branch[0]["branch_id"],'bm_id'=>$product[0]["bm_id"]));
                    if(count($info) != '0'){
                        $msg['error'] = '此门店和此基础物料关联关系已存在 ';
                        return false;
                    }
                    
                    $data = array(
                        'branch_id'=>$branch[0]["branch_id"],
                        'bm_id'=>$product[0]["bm_id"],
                    );
                    
                    $fileData['o2obranchproduct']['contents'][] = $data;
                    base_kvstore::instance('o2o_branch_product')->store('o2obranchproduct-'.$this->ioObj->cacheTime,$fileData);
                }
            }
        }
        
        return null;
        
    }
    
    function finish_import_csv(){
        base_kvstore::instance('o2o_branch_product')->fetch('o2obranchproduct-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('o2o_branch_product')->store('o2obranchproduct-'.$this->ioObj->cacheTime,'');

        $oQueue = app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;

        foreach ($aP['o2obranchproduct']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'门店物料关联导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'o2o',
                    'mdl' => 'branch_product'
                ),
                'worker'=>'o2o_branch_product_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        
        //如需要记录日志 后修改再用  先注释着 
//         $operationLogObj = app::get('ome')->model('operation_log');
//         $operationLogObj->write_log('basic_material_import@wms', 0, "批量导入基础物料,本次共导入". count($aP['basicm']['contents']) ."条记录!");
        
        return null;
    }
    
    function exportTemplate(){
        $title = $this->import_title();
        foreach($title as $k=>$v){
            $title[$k] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    //定义导入文件模版字段
    /**
     * import_title
     * @return mixed 返回值
     */
    public function import_title(){
        $title = array(
            '*:门店名称',
            '*:门店编码',
            '*:基础物料名称',
            '*:基础物料编码',
        );
        return $title;
    }
    
    function modifier_is_ctrl_store($row){
        switch ($row){
            case "1":
                $return_txt = "有";
                break;
            case "2":
                $return_txt = "-";
                break;
        }
        return $return_txt;
    }
    
    function modifier_status($row){
        switch ($row){
            case "1":
                $return_txt = "在售";
                break;
            case "2":
                $return_txt = "停售";
                break;
        }
        return $return_txt;
    }
    
    function modifier_is_bind($row){
        switch ($row){
            case "1":
                $return_txt = "未绑定";
                break;
            case "2":
                $return_txt = "绑定";
                break;
        }
        return $return_txt;
    }
    
    
    //扩展字段先定义
    function extra_cols(){
        return array(
            'column_store_name' => array('label'=>'关联门店','width'=>'150','func_suffix'=>'store_name',"order"=>"7"),
            'column_specifications' => array('label'=>'规格','width'=>'120','func_suffix'=>'specifications',"order"=>"6"),
            'column_type_name' => array('label'=>'分类','width'=>'100','func_suffix'=>'type_name',"order"=>"5"),
            'column_brand_name' => array('label'=>'品牌','width'=>'120','func_suffix'=>'brand_name',"order"=>"4"),
            'column_material_bn' => array('label'=>'基础物料编码','width'=>'120','func_suffix'=>'material_bn',"order"=>"3"),
            'column_material_name' => array('label'=>'基础物料名称','width'=>'260','func_suffix'=>'material_name',"order"=>"2"),
        );
    }
    
    function extra_store_name($rows){
        return kernel::single('o2o_extracolumn_branchproduct_storename')->process($rows);
    }
    
    function extra_material_name($rows){
        return kernel::single('o2o_extracolumn_branchproduct_materialname')->process($rows);
    }
    
    function extra_material_bn($rows){
        return kernel::single('o2o_extracolumn_branchproduct_materialbn')->process($rows);
    }
    
    function extra_specifications($rows){
        return kernel::single('o2o_extracolumn_branchproduct_specifications')->process($rows);
    }
    
    function extra_brand_name($rows){
        return kernel::single('o2o_extracolumn_branchproduct_brandname')->process($rows);
    }
    
    function extra_type_name($rows){
        return kernel::single('o2o_extracolumn_branchproduct_typename')->process($rows);
    }
    
}