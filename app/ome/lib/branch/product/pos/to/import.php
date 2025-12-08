<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_branch_product_pos_to_import {

    function run(&$cursor_id,$params)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');#基础物料条形码
        
        $bpObj = app::get($params['app'])->model($params['mdl']);
        $branchObj = app::get('ome')->model('branch');
        $branch_pos_obj = app::get('ome')->model('branch_pos');
        
        $Sdf = array();
        $bp = array();
        $Sdf = $params['sdfdata'];

        foreach ($Sdf as $v){
            $bp = array();
            //获取仓库ID
            $branch = $branchObj->dump(array('name'=>trim($v[4])), 'branch_id');
            $branch_pos = $branch_pos_obj->dump(array('store_position'=>trim($v[0]), 'branch_id'=>$branch['branch_id']), 'pos_id');

            if(!empty($v[2]) && $v[2]!=''){
                //$pFilter['barcode'] = $v[2];
                
                #查询条形码对应的bm_id
                $bm_ids    = $basicMaterialBarcode->getBmidListByBarcode($v[2]);
                $pFilter['bm_id']    = $bm_ids;
            }else{
                $pFilter['material_bn'] = $v[3];
            }
            
            $bMaterialRow    = $basicMaterialObj->dump($pFilter, 'bm_id');
            
            if ($branch['branch_id'])
            {
                if(!isset($bMaterialRow['bm_id']) || $bMaterialRow['bm_id']<=0){
                    continue;
                }

                $product_id = $bMaterialRow['bm_id'];
                $branch_id = $branch['branch_id'];
                $pos_id = $branch_pos['pos_id'];
                $libBranchProductPos->create_branch_pos($product_id, $branch_id, $pos_id);
            }
        }
        return false;
    }


    function process($post,&$msgList) {
        $return = $this->_localSaveFile();
        
        $data = $return['data'];

        if($return['rsp'] == 'fail'){
            return $return;
        }
        $msg = '';
        foreach ($data as $rowdata) {
            $validData = $this->valid($rowdata,$msg);
            
            if ($validData) {

                $this->unbundPos($rowdata);
            }else{
                $msgList[] = $msg;
            }
            

        }
        return array('rsp'=>'succ');

    }



    private function _localSaveFile(){
        
        if( !$_FILES['import_file']['name'] ){
          return  kernel::single('ome_func')->getErrorApiResponse("未上传文件");
        }
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
           
        $mdl = $_POST['model'];
        $app_id = $_POST['app'];

        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == substr($_FILES['import_file']['name'],-3 ) ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        if( !$oImportType ){
            return  kernel::single('ome_func')->getErrorApiResponse("导入格式不正确");
        }

        $contents = array();
        $oImportType->fgethandle($tmpFileHandle,$contents);

        fclose($tmpFileHandle);
        unset($contents[0]);
        
        $tm_contents = array();

        foreach($contents as $row){
            
            if(!empty($row[0]) && !empty($row[3]) && !empty($row[4])){
                $sdf = array(
                    'pos'=>$row[0],
                    'product_name' => $row[1],
                    'barcode'=>$row[2],
                    'bn'=>trim($row[3]),
                    'branch_name'=>$row[4],
                    
                );
                $tm_contents[] = $sdf;
            }
        }
        $contents = $tm_contents;

        if(empty($contents)){
            return  kernel::single('ome_func')->getErrorApiResponse("导入数据项为空");
        }else{
            return  kernel::single('ome_func')->getApiResponse($contents);
        }
    }

    /**
    * 检查货位和商品关系，并返回错误
    *
    * return bool
    */
    private function valid(&$data,&$msgerror) {
        $branchObj = app::get('ome')->model('branch');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $branchPosObj = app::get('ome')->model('branch_pos');
        $productPosObj = app::get('ome')->model('branch_product_pos');
        $msgerror = '';
        $product = $basicMaterialObj->getlist('bm_id',array('material_bn'=>$data['bn']),0,1);
        $branch = $this->getBranchIdByname($data['branch_name']);
 
        if (empty($branch)) {
            $msgerror.= $data['branch_name'].'不存在,';
            return false;
        }else{
            $data['branch_id'] = $branch['branch_id'];
        }

        $branch_pos = $branchPosObj->getlist('pos_id',array('branch_id'=>$data['branch_id'],'store_position'=>$data['pos']),0,1);
        $product_id = $product[0]['bm_id'];
        $pos_id = $branch_pos[0]['pos_id'];
        $productPos = $productPosObj->getlist('store',array('branch_id'=>$data['branch_id'],'product_id'=>$product_id,'pos_id'=>$pos_id),0,1);
        
        
        if (empty($product)) {
            $msgerror.= $data['bn'].'不存在,';
        }
        if (empty($branch_pos)) {
            $msgerror.= $data['bn'].'仓库和货品的关系不存在,';
        }
        if (empty($productPos)) {
            $msgerror.= $data['bn'].':货位关系不存在,';
        }else{
            if($productPos[0]['store']>0) {
                $msgerror.= $data['bn'].'货位库存大于0,';
            }
        }
        if (strlen($msgerror) > 0) {
            return false;
        }else{
            $data['product_id'] = $product_id;
            $data['pos_id'] = $pos_id;
           
            return true;
        }


    }

    /**
    * 解除商品,货位和仓库关系
    */
    private function unbundPos($data) {
        $result = kernel::database()->exec('DELETE FROM sdb_ome_branch_product_pos WHERE branch_id='.$data['branch_id'].' AND product_id='.$data['product_id'].' AND pos_id='.$data['pos_id']);

        return $result;
    }

    /**
    * 根据仓库名称返回仓库ID
    *
    */
    private function getBranchIdByname($branch_name){

        $branch = kernel::database()->select('select branch_id from sdb_ome_branch where name=\''.$branch_name.'\'');
        return $branch[0];

    }
}
