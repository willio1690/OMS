<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_ctl_admin_email extends desktop_controller {

    var $workground = 'setting_tools';
    public function index(){
        $rs_mail = app::get('monitor')->getConf('email.config');
        $this->pagedata['data'] = $rs_mail;
        $this->page('admin/email/config.html');
    }

    function save(){
        $this->begin('index.php?app=monitor&ctl=admin_email&act=index');
        $post = $_POST['mail'];
        $result = app::get('monitor')->setConf('email.config', $post);
        if($result){
            $this->end(true,app::get('desktop')->_('保存成功'));
        }else{
            $this->end(false,app::get('desktop')->_('保存失败'));
        }
    }

    function testEmail(){
        $this->display('admin/email/testemail.html');
    }

     function doTestemail(){
        $subject = app::get('desktop')->_("测试邮件");
        $body = app::get('desktop')->_("这是一封测试邮箱配置的邮件，您的网店能正常发送邮件。");
        $data['receiver'] = $_POST['acceptor'];
        $data['subject'] = $subject;
        $data['send_content'] = $body;
        list($rs, $msg) = kernel::single('monitor_event_trigger_notify_email')->send($data);
        if($rs) {
            //echo "已成功发送一封邮件，请查收";
        } else {
            echo $msg;
        }
    }
}