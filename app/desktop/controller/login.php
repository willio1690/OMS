<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/1/20 14:31:31
 * @describe: 控制器
 * ============================
 */
class desktop_ctl_login extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $params = array(
                'title'=>'登录日志',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'event_id desc',
        );
        
        $this->finder('desktop_mdl_login', $params);
    }

    /**
     * 获取账号信息
     *
     * @return void
     **/
    public function getUser()
    {
        $data = [
            'login_name' => kernel::single('base_view_helper')->modifier_cut($this->user->get_login_name(),'-1',strlen($this->user->get_login_name()) > 11 ?'****':'**',false,true),
            'user_mobile' => $this->user->get_mobile(),
            'user_name' => kernel::single('base_view_helper')->modifier_cut($this->user->get_name(),'-1',strlen($this->user->get_name()) > 11 ?'****':'**',false,true),
            'user_status' => $this->user->get_status(),
        ];

        $this->returnJson($data);
    }
}