<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料的队列任务导入最终执行Lib类
 * 20160811
 * @author wangjianjun 
 * @version 0.1
 */

class o2o_branch_product_to_import {

    //门店基础物料关联的队列任务执行
    function run(&$cursor_id,$params){
        $importObj = app::get($params['app'])->model($params['mdl']);
        $dataSdf = $params['sdfdata'];
        
        $dataList    = array();
        
        foreach ($dataSdf as $v){
            $temp_rs = $importObj->dump(array("branch_id"=>$v["branch_id"],"bm_id"=>$v["bm_id"]));
            if(!empty($temp_rs)){
                //存在供货关系关联关系
                continue;
            }else{
                $importData = array(
                        'branch_id' => $v["branch_id"],
                        'bm_id' => $v["bm_id"],
                );
                $importObj->insert($importData);
                
                //[创建]淘宝门店关联宝贝
                $dataList[$v['branch_id']][$v['bm_id']]    = $v['bm_id'];
            }
        }
        
        //[批量创建]淘宝门店关联宝贝
        if($dataList)
        {
            foreach ($dataList as $branch_id => $bm_ids)
            {
                $storeItemLib    = kernel::single('tbo2o_store_items');
                $result          = $storeItemLib->batchCreate($bm_ids, $branch_id);
            }
        }
        
        return false;
    }
    
}
