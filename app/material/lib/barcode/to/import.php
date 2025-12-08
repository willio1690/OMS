<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料关联条码的队列任务导入最终执行Lib类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class material_barcode_to_import {

    /**
     * 基础物料关联条码的队列任务执行
     * 
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */

    function run(&$cursor_id,$params,&$errmsg){
        $importObj = app::get($params['app'])->model($params['mdl']);
        $dataSdf = $params['sdfdata'];

        foreach ($dataSdf as $v){
            $importData = array();
            $importData['bm_id'] = $v[0];
            $importData['type'] = material_codebase::getBarcodeType();
            $importData['code'] = $v[1];
          
            $importObj->save($importData);
			$m = $importObj->db->errorinfo();
			if(!empty($m)){
				$errmsg.=$m.";";
			}
         }
         
        return false;
    }
}
