<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_operation_log{
        
    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations(){
        $operations = array(

            'bill' => array('name'=> '唯品会账单','type' => 'bill@vop'),
            'vreturn_diff' => ['name'=> '唯品会退供差异','type' => 'vreturn_diff@vop'],
        );

        return array('vop'=>$operations);
    }
}
?>