<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/8 16:51:42
 * @describe: 仓库选择
 * ============================
 */
class omeauto_branch_choose {

    /**
     * cmp_router_weight
     * @param mixed $a a
     * @param mixed $b b
     * @return mixed 返回值
     */

    public function cmp_router_weight($a, $b) {
        if($a['weight'] === $b['weight']) {
            return 0;
        }
        return $a['weight'] > $b['weight'] ? -1 : 1;
    }

    public function getSelectBid($tid,&$group,$branchInfo = array()) {
        $branchIds = $group->getBranchId();
        if(empty($branchIds)) {
            return 0;
        }
        if(count($branchIds) == 1) {
            return current($branchIds);
        }
        if(empty($tid)) {
            return reset($branchIds);
        }
        $objBranchGet = app::get('omeauto')->model('autobranchget');
        $bg = $objBranchGet->getList('*', array('tid'=>$tid));
        if(empty($bg)) {
            return reset($branchIds);
        }
        uasort($bg, array($this, 'cmp_router_weight'));
        foreach ($bg as $v) {
            $bgData = $group->getBranchGroup();
            if(count($bgData) == 1) {
                $bgRowData = current($bgData);
                return reset($bgRowData['branch_id']);
            }
            try {
                $className = 'omeauto_branch_' . $v['classify'];
                if(class_exists($className)) {
                    $branchIds = kernel::single($className)->process($branchIds, $group, $branchInfo);
                    if(count($branchIds) == 1) {
                        return current($branchIds);
                    }
                }
            } catch (Exception $e){}
        }
        return reset($branchIds);
    }
}