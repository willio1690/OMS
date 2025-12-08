<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_cpfr
{
    public $addon_cols = 'bill_status,adjust_type,cpfr_bn,cpfr_id';
    
    public $column_edit = "操作";
    public $column_edit_width = "80";
    public $column_edit_order = "-1";
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        $adjust_type = $row[$this->col_prefix.'adjust_type'];
        $cpfr_bn = $row[$this->col_prefix.'cpfr_bn'];
        $cpfr_id = intval($row['cpfr_id']);
        $confirm= '<a class="lnk" href="javascript:if(confirm(\'确认单据'.$cpfr_bn.'?\')) {W.page(\'index.php?app=console&ctl=admin_cpfr&act=singleConfirm&p[0]='.$cpfr_id.'&finder_id='.$_GET['_finder']['finder_id'].'&finder_vid='.$_GET['finder_vid'].'\');};">
                            确认</a>';
        if(in_array($row[$this->col_prefix.'bill_status'], ['1']) && $adjust_type == 'import') {
            return $confirm;
        }
        return '';
    }

    public $detail_items = '配货明细';
    /**
     * detail_items
     * @param mixed $cpfr_id ID
     * @return mixed 返回值
     */
    public function detail_items($cpfr_id)
    {
        $render = app::get('console')->render();
        
        /***
        $items = app::get('console')->model('cpfr_items')->getList('*', array('cpfr_id' => $cpfr_id));

        @ini_set('memory_limit','1024M');
        $render->pagedata['items'] = $items;
        return $render->fetch('admin/finder/cpfr/items.html');
        ***/
        
        $render->pagedata['cpfr_id'] = $cpfr_id;
        
        return $render->fetch('admin/cpfr/cpfr_item.html');
    }


}
