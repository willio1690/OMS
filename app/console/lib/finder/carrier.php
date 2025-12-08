<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_carrier{
    
    var $addon_cols    ='carrier_isvalid';
    
    var $column_carrier_isvalid = '是否启用';
    var $column_carrier_isvalid_width = 120;
    var $column_carrier_isvalid_order = 10;
    function column_carrier_isvalid($row)
    {
        if($row[$this->col_prefix .'carrier_isvalid'] == 1)
        {
            return '启用';
        }
        
        return '停用';
    }
}