<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/5/6
 * Time: 14:13
 */
class console_ctl_admin_useful_log extends desktop_controller
{
    var $name = "仓库库存查看";
    var $workground = "storage_center";
    /**
     * index
     * @return mixed 返回值
     */

    public function index(){
        $params = array(
            'title'=>'有效期商品出入明细',
            'base_filter' => array(),
            'actions' => array(),
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_selectrow'=>false,
            'use_view_tab' => false,
            'orderBy' => 'life_log_id desc'
        );
        $this->finder('console_mdl_useful_life_log', $params);
    }
}