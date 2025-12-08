<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/12/5 10:17:17
 * @describe: 加工单
 * ============================
 */
class console_ctl_admin_wms_storeprocess extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $params = array(
            'title'=>'加工单列表',
            'use_buildin_set_tag'=>false,
            'use_buildin_filter'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_recycle'=>false,
            'actions'=>$actions,
            'orderBy'=>'id desc',
        );
        
        $this->finder('console_mdl_wms_storeprocess', $params);
    }
}