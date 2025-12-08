<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_mdl_appropriation extends dbeav_model{

    function to_savestore($adata,$memo,$op_name,$appropriation=array())
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $libBranchProduct    = kernel::single('ome_branch_product');
        
        $oAppropriation_items = $this->app->model("appropriation_items");
        
        $op_name = $op_name=='' ? '未知' : $op_name;
        $appro_data = array(
            'appropriation_id'=>$appropriation['appropriation_id'],
            'type'=>$appropriation['type'],
            'create_time'=>time(),
            'operator_name'=>$op_name,
            'memo'=>$memo
        );
        $this->save($appro_data);
  
        foreach($adata as $k=>$v)
        {
            $product    = $basicMaterialObj->dump(array('bm_id'=>$v['product_id']), 'bm_id, material_bn, material_name');
            
            $from_branch_id=$v['from_branch_id'];
            $to_branch_id=$v['to_branch_id'];
            $from_pos_id=$v['from_pos_id'];
            $to_pos_id=$v['to_pos_id'];
          
            $add_store_data =array(
                 'pos_id'=>$to_pos_id,'product_id'=>$v['product_id'],'num'=>$v['num'],'branch_id'=>$to_branch_id
            );
        
           
            $lower_store_data= array(
               'pos_id'=>$from_pos_id,'product_id'=>$v['product_id'],'num'=>$v['num'],'branch_id'=>$from_branch_id);
            $items_data = array(
                'appropriation_id'=>$appro_data['appropriation_id'],
                'bn'=>$product['material_bn'],
                'product_name'=>$product['material_name'],
                'product_id'=>$v['product_id'],
                'from_branch_id'=>$from_branch_id==''? 0:$from_branch_id,
                'from_pos_id'=>$from_pos_id=='' ? 0:$from_pos_id,
                'to_branch_id'=>$to_branch_id=='' ? 0:$to_branch_id,
                'to_pos_id'=>$to_pos_id=='' ? 0:$to_pos_id,
                'num'=>$v['num']
                );
            $oAppropriation_items->save($items_data);
              /*当货位号不相同时。是不同仓库不同货位上进行调拔。*/
        
            if($to_pos_id==''){
                $libBranchProduct->operate_store($lower_store_data,'lower');
                
            }else if($from_pos_id==''){
                $libBranchProduct->operate_store($add_store_data,'add');
            }
            else{
             $libBranchProduct->operate_store($add_store_data,'add');
             $libBranchProduct->operate_store($lower_store_data,'lower');
            }
            
        }
     

  }
  
    function searchOptions(){
        return array(
                
            );
    }
	
		
	function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
	{
	    $basicMaterialObj = app::get('material')->model('basic_material');
        
        if (empty($row)){
            return true;
        }
        $mark = false;

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            
            return $titleRs;
        }else{
			$re = base_kvstore::instance('purchase_appropriation')->fetch('appropriation-'.$this->ioObj->cacheTime,$fileData);
        	if( !$re ) $fileData = array();
		    
			$error_info = array('货号','货品名称','原仓库/货位','新仓库/货位','数量');
			foreach($row as $k=>$info){
			    if(!$info){
				     $msg['error'] = $error_info[$k].'必须填写!';
                     return false;
				}
			}
			
			 $oBranch = app::get('ome')->model('branch');
			 $oBranchPos = app::get('ome')->model('branch_pos');
			 $oBranchProduct = app::get('ome')->model('branch_product');
             $oBranchProductPos = app::get('ome')->model('branch_product_pos');
			 
			 $product_bn = $row[0];
			 $product_name = $row[1];
			 $arrFromBranchPos = explode('/',$row[2]);
			 if(!$arrFromBranchPos[0] || !$arrFromBranchPos[1]){
			     $msg['error'] = '调出仓库或者货位必须填写完整!';
                 return false;
			 }
			 $from_branch = $arrFromBranchPos[0];
			 $from_pos = $arrFromBranchPos[1];
			 
			 $arrToBranchPos = explode('/',$row[3]);
			 if(!$arrToBranchPos[0] || !$arrToBranchPos[1]){
			     $msg['error'] = '调入仓库或者货位必须填写完整!';
                 return false;
			 }
			 $to_branch = $arrToBranchPos[0];
			 $to_pos = $arrToBranchPos[1];
			 $nums = intval($row[4]);
			 
			 if( $nums <= 0 ){
			     $msg['error'] = '调出数量无效!';
                 return false;
			 }
			 
			 $product = $basicMaterialObj->dump(array('material_bn'=>$product_bn), 'bm_id, material_bn, material_name');
			 
			 if(!$product){
			     $msg['error'] = '没有此商品: '.$product_bn .'!';
				 return false;
			 }
			 
			 if($product['material_name'] != $product_name){
			     $msg['error'] = '货号与货品名称不一致: '.$product_name.'('.$product_bn.')!';
				 return false;
			 }
			 
			 $from_branch_id = $oBranch->dump(array('name'=>$from_branch),'branch_id');
			 if(!$from_branch_id){
			     $msg['error'] = '调出仓库不存在: '.$from_branch.'!';
				 return false;
			 }
			 
			 $from_pos_id = $oBranchPos->dump(array('store_position'=>$from_pos,'branch_id'=>$from_branch_id['branch_id']),'pos_id');
			 if(!$from_pos_id){
			     $msg['error'] = '调出货位不存在: '.$from_pos.'!';
				 return false;
			 }
			 
			 $from_branch_product = $oBranchProduct->dump(array('branch_id'=>$from_branch_id['branch_id'],'product_id'=>$product['bm_id']),'*');
             if( !$from_branch_product ){
                  $msg['error'] = '调出仓库和商品关系未建立,不可以调拔!';
                  return false;
              }
			  
			  $from_product_pos = $oBranchProductPos->dump(array('pos_id'=>$from_pos_id['pos_id'],'product_id'=>$product['bm_id']),'*');
              if( !$from_product_pos ){
                  $msg['error'] = '调出货位和商品关系未建立,不可以调拔!';
                  return false;
              }
			
			 if($from_product_pos['store'] < $nums){
			      $msg['error'] = '调出货位所剩数量不足以本次调拔!';
				  return false;
			 }
			 
			 $to_branch_id = $oBranch->dump(array('name'=>$to_branch),'branch_id');
			 if(!$to_branch_id){
			     $msg['error'] = '调入仓库不存在: '.$from_branch.'!';
				 return false;
			 }
			 
			 $to_pos_id = $oBranchPos->dump(array('store_position'=>$to_pos,'branch_id'=>$to_branch_id['branch_id']),'pos_id');
			 if(!$to_pos_id){
			     $msg['error'] = '调入货位不存在: '.$from_pos.'!';
				 return false;
			 }
			 
			 $data = array();
			 $data['product_id'] = $product['bm_id'];
			 $data['from_branch_id'] = $from_branch_id['branch_id'];
			 $data['from_pos_id'] = $from_pos_id['pos_id'];
			 $data['to_branch_id'] = $to_branch_id['branch_id'];
			 $data['to_pos_id'] = $to_pos_id['pos_id'];
			 $data['num'] = $nums;

			 $fileData['appropriation']['contents'][] = $data;
			 base_kvstore::instance('purchase_appropriation')->store('appropriation-'.$this->ioObj->cacheTime,$fileData);
        }
        return null;
    }
	
	 function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
       	return null;
    }
	
	 function finish_import_csv(){  
        base_kvstore::instance('purchase_appropriation')->fetch('appropriation-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('purchase_appropriation')->store('appropriation-'.$this->ioObj->cacheTime,'');
        $oQueue = app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
      
        $count = 0;
        $limit = 50;
        $page = 0;
        
        foreach ($aP['appropriation']['contents'] as $k => $aPi){
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
                'queue_title'=>'调拨单导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>array('op_name'=>kernel::single('desktop_user')->get_name(),'list'=>$v),
                    'app' => 'purchase',
                    'mdl' => 'appropriation'
                ),
                'worker'=>'purchase_appropriation_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }
	
	function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }
	
	 function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }
     
    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['appropriation'] = array(
                    '*:货号' => 'bn',
                    '*:货品名称' => 'name',
                    '*:原仓库/货位' => 'old_branch_pos',
                    '*:新仓库/货位' => 'new_branch_pos',
                    '*:数量' => 'nums'
                );
             
                break;
        }
        $this->ioTitle[$ioType]['appropriation'] = array_keys( $this->oSchema[$ioType]['appropriation'] );
        return $this->ioTitle[$ioType][$filter];
     }

    /**
     * 获取DataByBranch
     * @param mixed $op_name op_name
     * @param mixed $branch branch
     * @return mixed 返回结果
     */
    public function getDataByBranch($op_name,$branch){
        $time = strtotime(date('Y-m-d',time()));
        $sql = 'SELECT A.appropriation_id FROM '.
            kernel::database()->prefix.'purchase_appropriation as A LEFT JOIN '.
            kernel::database()->prefix.'purchase_appropriation_items as I ON A.appropriation_id=I.appropriation_id '.
            'WHERE A.type=\'1\' and  A.operator_name=\''.$op_name.'\' and I.from_branch_id=\''.$branch.
            '\' and A.create_time>=\''.$time.'\' and A.create_time<\''.($time+86400).'\'';
        $row = $this->db->select($sql);
        if($row){
            return intval($row[0]['appropriation_id']);
        }else{
            return 0;
        }
    }
}

?>
