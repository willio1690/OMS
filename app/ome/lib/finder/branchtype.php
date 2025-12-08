<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_branchtype{

    var $addon_cols = 'source';
    public $column_edit       = "操作";
    public $column_edit_width = "280";
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){

        $source = $row[$this->col_prefix.'source'];

        if($source == 'system') return ;

        $finder_id = $_GET['_finder']['finder_id'];
        $type_id = $row['type_id'];

        $buttons = [];

        $buttons[] = '<a href="index.php?app=ome&ctl=admin_branchtype&act=edit&p[0]='.$row ['type_id'].'&finder_id='.$finder_id.'" target="dialog::{width:600,height:300,title:\'编辑\'}">编辑</a>';


        $buttons[] = <<<BTN
            <a class="c-red" onclick="if(confirm('确认删除？')){ W.page('index.php?app=ome&ctl=admin_branchtype&act=del&p[0]={$type_id}&finder_id={$finder_id}')}" >删除</a>
BTN;
        return implode('&nbsp;',$buttons);
    }


   

}
