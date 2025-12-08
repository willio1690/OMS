<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_gift_rule extends dbeav_model 
{


    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    function io_title( $filter=null){
        switch( $filter ){
            case 'csv':
            default:
                $this->oSchema['csv'][$filter] = array(
                    '*:销售物料编码' => 'sales_material_bn',
                    
                   
                );
                
                break;
        }
        
        return $this->ioTitle['csv'][$filter] = array_keys( $this->oSchema['csv'][$filter] );   
     }

     function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
     {
        $salesMaterialObj    = app::get('material')->model('sales_material');
        $this->aa++;
        $mark = false;
        $fileData = $this->kvdata;
      
        if( !$fileData ) $fileData = array();

        if( substr($row[0],0,2) == '*:' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{

            $sales_material_bn = trim($row[0]);

            
            if ($sales_material_bn) 
            {
               
                $products    = $salesMaterialObj->dump(array('sales_material_bn'=>$sales_material_bn,'sales_material_type'=>array('1','2','5')), 'sm_id');
                
                if(!$products){
                    $msg['error'] = '货号不存在 '.$bn;
                    return false;
                }

               
                $fileData[] = $sales_material_bn;
               

                
            }
            $this->kvdata = $fileData;
            
        }
        return null;
     }

     function prepared_import_csv(){
        set_time_limit(0);

    }

    function finish_import_csv(){
        $applyObj = $this->app->model('gift_rule');
        $data = $this->kvdata; unset($this->kvdata);

        $id = intval($_POST['id']);
        $apply = $applyObj->dump(array('id'=>$id),'filter_arr');

        $old_filter_arr = $apply['filter_arr'] ? json_decode($apply['filter_arr'],true) : array();

        if($data){
      		$old_goods_bn = $old_filter_arr['buy_goods']['goods_bn'] ? $old_filter_arr['buy_goods']['goods_bn'] : array();
      		$goods_bn = $data;
            $apply_goods = array_merge($old_goods_bn,$goods_bn);
            $apply_goods = array_unique($apply_goods);
            $old_filter_arr['buy_goods']['goods_bn'] = $apply_goods;
            $filter_arr = json_encode($old_filter_arr);
        
            $applyObj->update(array('filter_arr'=>$filter_arr),array('id'=>$id));
        }
        
       
        
        return null;
    }

    /**
     * modifier_id
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_id($row){
        if($row){
            return '#'.str_pad($row,8,0,STR_PAD_LEFT);
        }
    }

    /**
     * modifier_status
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_status($row)
    {
        if ($row == '1') {
            $row = "<span style='color:green;'>开启</span>";
        }else if($row == '0'){
           $row = "<span style='color:red;'>关闭</span>";
        }
        return $row;
    }
}