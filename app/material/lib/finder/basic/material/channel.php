<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_finder_basic_material_channel
{
    function row_style($row)
    {
        $style = '';
        if ($row[$this->col_prefix.'is_error'] == '1') {
            $style .= 'list-warning';
        }
        return $style;
    }
}