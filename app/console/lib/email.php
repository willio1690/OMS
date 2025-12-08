<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_email{

    #邮件发送
    /**
     * send
     * @param mixed $receiveMail receiveMail
     * @param mixed $subject subject
     * @param mixed $body body
     * @param mixed $attachment attachment
     * @param mixed $SMTPDebug SMTPDebug
     * @return mixed 返回值
     */
    public function send($receiveMail, $subject, $body, $attachment = [], $SMTPDebug = 0){
        $config = $this->get_config();
        //收件信息
        $receive_mail = explode(';',$receiveMail);
        $to = array();
        foreach($receive_mail as $receive){
            if(!$receive){
                continue;
            }
            $receive = explode('#',$receive);
            if($receive[1]) {
                $to[] = array('email'=>$receive[0],'name'=>$receive[1]);
            }
        }
        if(!$to || !$subject || !$body || !$config){
            $err_msg = '邮件发送的必要信息缺失';
            return [false, $err_msg];
        }
        
        //init lib
        $emailLib = new console_email_email;
        $emailLib->SMTPDebug = $SMTPDebug;
        $emailLib->CharSet = "UTF-8";
        $emailLib->IsSMTP();
        $emailLib->SMTPAuth = true;

        if($config['smtp_ssl'] == '1'){
            $emailLib->SMTPSecure = 'ssl';
        }

        $emailLib->isHTML(true);

        $emailLib->Host = $config['smtp_server'];
        $emailLib->Port = $config['smtp_port'];
        $emailLib->Username = $config['email'];
        $emailLib->From = $config['email'];
        $emailLib->FromName = $config['name'];
        $emailLib->Password = $config['psw'];

        //set send list
        foreach($to as $send){
            if($send['email'] && $send['name']){
                $emailLib->AddAddress($send['email'], $send['name']);
            }
        }

        //add attachment
        if($attachment){
            $emailLib->AddAttachment($attachment);
        }

        $emailLib->Subject = $subject;
        $emailLib->Body = $body;

        //发送邮件
        try {
            $emailLib->Send();
            return [true];
        } catch (Exception $e) {
            $err_msg = $emailLib->ErrorInfo;
            return [false, $err_msg];
        }
    }

    //获取发送邮件设置
    /**
     * 获取_config
     * @return mixed 返回结果
     */
    public function get_config(){
        $rs_mail = app::get('console')->getConf('email.config');
        return $rs_mail;
    }

    //获取邮件附件全路径
    /**
     * 获取_send_email_attachement_path
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function get_send_email_attachement_path($type){
        //获取邮件附件存放目录
        $file_dir = DATA_DIR.'/email_attachement';
        if(!is_dir($file_dir)){
            utils::mkdir_p($file_dir, 0777, ture);
        }
        //获取邮件附件名
        $filename = date("Y-m-d",time()).$type.'.csv';
        $attachement_path = $file_dir.'/'.$filename;
        return $attachement_path;
    }
    
}