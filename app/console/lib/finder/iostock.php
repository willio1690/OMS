<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_iostock{
    var $detail_basic = '详情';

    /**
     * detail_basic
     * @param mixed $bn_branch bn_branch
     * @return mixed 返回值
     */
    public function detail_basic($bn_branch)
    {
        // 取货号和库ID
        $arr        = explode('*$**',$bn_branch);
        $bn         = $arr[1];
        $branch_id  = $arr[0];

        // 整理查询条件
        $time_from  = $_GET['time_from'];
        $time_to    = $_GET['time_to'];
        $filter     = array('time_from' => $time_from, 'time_to' => $time_to);

        // 查询
        $mels       = app::get('console')->model('interface_iostocksearchs');
        $row        = $mels->details($bn, $branch_id, $filter);
        
        $render = app::get('console')->render();
        $render->pagedata['rows'] = $row;

        return $render->display('admin/detail_goods.html');
    }
}