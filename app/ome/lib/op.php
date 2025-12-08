<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_op{

    /**
     * 获取BranchByOp
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getBranchByOp($op_id)
    {
        $bps = array();
        $oBops = app::get('ome')->model('branch_ops');
        $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
        if ($bops_list){
            $bps = array_map('current',$bops_list);
        }

        return $bps;
    }

}