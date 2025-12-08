<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgstockcost_ctl_dailystock extends desktop_controller
{

    public $workground = "invoice_center";

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $baseFilter = [];
        if (!isset($_POST['stock_date'])) {
            $baseFilter['stock_date'] = date('Y-m-d',strtotime('-1 days'));
        }
        $date = $baseFilter['stock_date'] ? $baseFilter['stock_date'] : $_POST['stock_date'];

        $this->finder('tgstockcost_mdl_dailystock', array(
            'title'                  => '进销存统计'.'<em class="c-red">默认显示 '.$date.' 数据</em>',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => true,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_buildin_setcol'     => false,
            'base_filter' => $baseFilter,
            'orderBy' => 'id desc',
        ));
    }
}
