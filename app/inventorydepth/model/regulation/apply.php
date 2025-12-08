<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 规则应用模型类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_mdl_regulation_apply extends dbeav_model 
{
    public $defaultOrder = 'priority asc';

    public function modifier_using($row) 
    {
        $using = '';
        if ($row == 'true') {
            $using = '<span style="color:green;">已启用</span>';
        } else {
            $using = '<span style="color:red;">未启用</span>';
        }

        return $using;
    }

    //定义导入文件模版字段
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = $v;
        }
        return $title;
    }

    function io_title( $filter=null){
        switch( $filter ){
            case 'shop_sku_id':
                $this->oSchema['csv'][$filter] = array(
                    '*:SKU ID' => 'shop_sku_id',
                );  
                break;
            case 'csv':
            default:
                $this->oSchema['csv'][$filter] = array(
                    '*:销售物料编码' => 'sales_material_bn',
                    '*:销售物料名称' => 'sales_material_name',
                    '*:销售物料类型' => 'sales_material_type',
                );
                
                break;
        }
        
        return $this->ioTitle['csv'][$filter] = array_keys( $this->oSchema['csv'][$filter] );   
     }

     function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $salesMaterialObj    = app::get('material')->model('sales_material');
        $this->aa++;
        $mark = false;
        $fileData = $this->kvdata['main'];
        if(!$fileData){
            $fileData = array();
        }
        
        if( substr($row[0],0,2) == '*:' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            $this->kvdata['title'] = $row[0];
            return $titleRs;
        }else{
            //导入SKU ID
            if ($this->kvdata['title'] == '*:SKU ID' && $shop_sku_id = trim($row[0])) {
                $skusObj = app::get('inventorydepth')->model('shop_skus');
                $info = $skusObj->db_dump(['shop_sku_id'=>$shop_sku_id]);
                if (!$info) {
                    $msg['error'] = 'SKU ID不存在 '.$shop_sku_id;
                    return false;
                }
                $fileData['shop_sku_id'][] = $shop_sku_id;
            }else{
                //导入销售物料
                $sales_material_bn = trim($row[0]); //销售物料编码
                //$sales_material_type_name = $row[1]; //销售物料类型名称
                if ($sales_material_bn) {
                    $productInfo = $salesMaterialObj->dump(array('sales_material_bn'=>$sales_material_bn), 'sm_id');
                    if(!$productInfo){
                        $msg['error'] = '销售物料不存在 '.$sales_material_bn;
                        return false;
                    }
                    $fileData['smIds'][] = $productInfo['sm_id'];
                }
            }
            
            $this->kvdata['main'] = $fileData;
        }
        return null;
     }

     function prepared_import_csv(){
        set_time_limit(0);
    }

    function finish_import_csv(){
        $applyObj = $this->app->model('regulation_apply');
        $data = $this->kvdata['main']; 
        $title = $this->kvdata['title']; 
        unset($this->kvdata);
        $id = intval($_POST['id']);
        
        //导入SKU ID
        if ($title == '*:SKU ID') {
            $update_data = [];
            $update_data['shop_sku_id'] = implode(',', $data['shop_sku_id']);
        } else {
            //导入销售物料
            $import_sm_ids = [];
            if(isset($data['smIds'])){
                $import_sm_ids = $data['smIds'];
            }
            
            $apply = $applyObj->dump(array('id'=>$id),'*');
            if($apply['apply_goods'] == "_ALL_" && $import_sm_ids){
                    $old_apply_goods = array();
            }else{
                $old_apply_goods = $apply['apply_goods'] ? explode(',',$apply['apply_goods']) : array();
            }
            
            $update_data = array();
            $apply_goods = array_merge($import_sm_ids, $old_apply_goods);
            $update_data['apply_goods'] = implode(',',array_unique($apply_goods));
        }
        
        //update
        $applyObj->update($update_data, array('id'=>$id));
        return null;
    }
}
