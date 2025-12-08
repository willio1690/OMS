<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_ctl_admin_setting extends desktop_controller
{

    public $workground = 'setting_tools';
    public function index()
    {
        $this->pagedata['email']  = app::get('monitor')->getConf('email.config');
        $this->pagedata['workwx'] = app::get('monitor')->getConf('workwx.config');
        
        $this->pagedata['dingding'] = app::get('monitor')->getConf('dingding.config');
        $this->page('admin/setting/index.html');
    }

    public function save()
    {
        $this->begin();

        app::get('monitor')->setConf('email.config', $_POST['email']);

        app::get('monitor')->setConf('workwx.config', $_POST['workwx']);

        app::get('monitor')->setConf('dingding.config', $_POST['dingding']);

        $this->end(true, app::get('desktop')->_('保存成功'));
    }
}
