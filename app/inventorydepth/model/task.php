<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class inventorydepth_mdl_task extends dbeav_model {

    //定义导入文件模版字段
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
                    '*:商品编码' => 'product_bn',
                    '*:商品类型' => 'product_type',

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
            $sales_material_type = ($row[1]=='组合') ? '2' : '1' ;

            if ($sales_material_bn)
            {

                $products    = $salesMaterialObj->dump(array('sales_material_bn'=>$sales_material_bn,'sales_material_type'=>$sales_material_type), 'sm_id,sales_material_name');

                if(!$products){
                    $msg['error'] = '货号不存在 '.$sales_material_bn;
                    return false;
                }





                $fileData[] = array('product_type'=>$sales_material_type,'product_id'=>$products['sm_id'],'product_bn'=>$sales_material_bn,'product_name'=>$products['sales_material_name']);



            }
            $this->kvdata = $fileData;

        }
        return null;
     }

     function prepared_import_csv(){
        set_time_limit(0);

    }

    function finish_import_csv(){
        $skuObj = $this->app->model('task_skus');
        $data = $this->kvdata; unset($this->kvdata);

        $task_id = intval($_POST['task_id']);
        $taskObj = $this->app->model('task');
        $task_detail = $taskObj->dump(array('task_id'=>$task_id),'shop_id');
        foreach($data as $v){
            $sku_detail = $skuObj->dump(array('task_id'=>$task_id,'product_type'=>$v['product_type'],'product_id'=>$v['product_id']));
            if(!$sku_detail){
                $sku_data = array(

                    'task_id'   =>$task_id,
                    'product_type'  =>  $v['product_type'],
                    'product_id'    =>  $v['product_id'],
                    'product_bn'    =>  $v['product_bn'],
                    'product_name'  =>  $v['product_name'],
                    'shop_id'       =>  $task_detail['shop_id'],
                );
                $skuObj->save($sku_data);
            }
        }



        return null;
    }

    public function pre_recycle($rows)
    {

        foreach ($rows as $key=>$row) {
            $task_id[] = $row['task_id'];
        }
        $task = $this->app->model('task')->getList('task_id',array('task_id'=>$task_id,'disabled'=>'true'),0,1);
        if ($task) {
            $this->recycle_msg = '活动为启用状态，无法进行删除！';
            return false;
        }

        $this->db->exec("DELETE FROM sdb_inventorydepth_task_skus WHERE task_id in(".implode(',',$task_id).")");
        return true;
    }


}
