<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/9/8 17:16:49
 * @describe: 类
 * ============================
 */
abstract class omeauto_branch_abstract {

    /**
     * 优选仓库
     * @param  array $branchIds 仓库ID
     * @param  array $group    订单分组
     * @param  array $branchInfo    仓库库存信息
     * @return array            仓库ID
     */
    abstract public function process($branchIds, &$group, $branchInfo);
}