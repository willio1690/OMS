<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface ome_branch_flow_interface
{
    /**
     * 显示页面内容
     *
     * @return void
     * @author 
     **/
    public function getContent($id);

    /**
     * 获取仓
     *
     * @return void
     * @author 
     **/
    public function getBranchList();

    /**
     * 列表展示
     *
     * @return void
     * @author 
     **/
    public function translateContent($content);
}