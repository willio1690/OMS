<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料唯一码Lib类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class material_serial{

    /**
     * 
     * 检查基础物料是否是唯一码类型
     * @param Int $id 基础物料ID
     */

    public function checkSerialById($id){
        $basicMConfObj    = app::get('material')->model('basic_material');
        $basicInfo = $basicMConfObj->dump(array('bm_id'=>$id, 'serial_number'=>'true'), 'bm_id');
        return $basicInfo ? true : false;
    }
}