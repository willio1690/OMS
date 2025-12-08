<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaniostockorder_operation_log
{
    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations()
    {
        $operations = array(
            'create_iostock'  => array('name' => '新建出入库', 'type' => 'iso@taoguaniostockorder'),
            'docheck_iostock' => array('name' => '出入库审核', 'type' => 'iso@taoguaniostockorder'),
            'edit_iostock'    => array('name' => '出入库编辑', 'type' => 'iso@taoguaniostockorder'),
            'check_defective' => array('name' => '出入库残损确认', 'type' => 'iso@taoguaniostockorder'),
        );
        return array('taoguaniostockorder' => $operations);
    }
}

?>