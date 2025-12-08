<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_inventory_snapshot
{
    public $column_opt       = '操作';
    public $column_opt_order = 1;
    /**
     * column_opt
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_opt($row)
    {
        $buttons = [];

        $url = 'index.php?app=desktop&act=alertpages&goto='.urlencode(sprintf("index.php?app=console&ctl=admin_inventory_snapshot&p[]=%s&act=itemIndex&finder_vid=%s",$row['id'], $_GET['finder_vid']));

        $buttons['items'] = sprintf('<a target="_blank" href="%s">查看明细</a>',$url);

        return implode(' | ', $buttons);
    }
}
