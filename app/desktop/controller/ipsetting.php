<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ShopEx licence
 *
 * @IP地址白名单
 * @2016-11-16
 * @liwei@shopex.cn
 *
 */
class desktop_ctl_ipsetting extends desktop_controller{
    
    var $workground = 'desktop_ctl_system';
    
    function index(){
        if((kernel::single('desktop_user')->is_super())!='1'){
            die("<h3>对不起，您无权限操作当前页面！</h3>");
        }
        $configIPdata = app::get('desktop')->getConf('ip_setting_white_list');
        $this->pagedata['ip_addr'] = $configIPdata['ip_addr'];
        $this->pagedata['user_name'] = $configIPdata['user_name'];
        $this->pagedata['lasttime'] = $configIPdata['lasttime']?(date("Y-m-d H:i:s",$configIPdata['lasttime'])):'';
        $this->page('ipsetting.html');
    }
    
    //@保存新增IP地址
    /**
     * 保存
     * @return mixed 返回操作结果
     */

    public function save(){
        $this->begin('index.php?app=desktop&ctl=ipsetting&act=index');
        $op_name = kernel::single('desktop_user')->get_login_name();

        $remote_ip = kernel::single('desktop_ip')->getIp();
        if($_POST['ip_addr'] && kernel::single('desktop_ip')->limit($_POST)){
            $this->end(false,app::get('desktop')->_('保存失败，当前IP['.$remote_ip.']必须包含！'));
        }

        $saveData = array(
            'ip_addr'   =>$_POST['ip_addr'],
            'user_name' =>$op_name,
            'lasttime'  =>time(),
        );

        $result = app::get('desktop')->setConf('ip_setting_white_list',$saveData);
        $this->end(true,app::get('desktop')->_('保存成功'));
    }

}
