<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_finder_syncproduct
{

   var $addon_cols ='bm_id';
    var $column_skunum = 'SKU统计';
    var $column_skunum_width = '80';
    var $column_skunum_order = COLUMN_IN_TAIL;
    function column_skunum($row){
        $priceMdl = app::get('pos')->model('productprice');
        $bm_id = $row[$this->col_prefix.'bm_id'];

        $filter = array('bm_id'=>$bm_id);
        
        $count = $priceMdl->count($filter);
        return "<span class=show_list bm_id=".$filter['bm_id']."><a >".$count."</a></span>";
    }  
}
