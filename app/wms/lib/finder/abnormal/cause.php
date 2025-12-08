<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_finder_abnormal_cause{
    
    var $column_edit = '操作';
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=wms&ctl=admin_abnormal_cause&act=edit&ac_id='.$row['ac_id'].'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
    }
    
}