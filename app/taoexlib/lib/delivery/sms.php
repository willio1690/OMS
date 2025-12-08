<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @description 发货发送短信
 * @access public
 * @param void
 * @return void
 */
class  taoexlib_delivery_sms
{
    //门店提醒短信发送
    public function o2oSendMessage($sendType,$sendArr,$content=NULL){
        if(empty($sendArr)){
            return false;
        }
        if(!$sendArr["ship_mobile"] || !$sendArr["ship_name"] || !$sendArr["store_name"]  || !$sendArr["store_contact_tel"]){
            return false;
        }
        
        if($sendType == "pickUpInStore"){
            if(!$sendArr["pickup_bn"] || !$sendArr["store_addr"]){
                return false;
            }
        }
        
        //获取短信模板内容
        $rule_sample_mdl = app::get('taoexlib')->model('sms_bind');
        if(!$content){
            $contentinfo = $rule_sample_mdl->getOtherSmsContent($sendType);
            if (!$contentinfo['content']) {
                return false;
            }else{
                $content = $contentinfo['content'];
                $sendArr["tplid"] = $contentinfo['tplid'];
            }
        }
        
        //门店自提
        if($sendType == "pickUpInStore"){
            //替换签名 获取完整短信日志content
            $find = array('{收货人}','{提货单}','{校验码}','{门店名称}','{短信签名}','{门店地址}','{门店联系电话}');
            //在关闭销单校验码状态下 不生成校验码 所以在发送日志和手机上收到的校验码显示“无”
            $mobile_pkcode_display = '******';
            if (!$sendArr["pickup_code"]){
                $sendArr["pickup_code"] = "无";
                $mobile_pkcode_display = $sendArr["pickup_code"];
            }
            $replace = array($sendArr['ship_name'],'******',$mobile_pkcode_display,$sendArr['store_name'],"【".$sendArr['store_name']."】",$sendArr['store_addr'],$sendArr['store_contact_tel']);
        }
        
        //门店配送
        if($sendType == "storeDelivery"){
            //替换签名 获取完整短信日志content
            $find = array('{收货人}','{门店名称}','{短信签名}','{门店联系电话}');
            $replace = array($sendArr['ship_name'],$sendArr['store_name'],"【".$sendArr['store_name']."】",$sendArr['store_contact_tel']);
        }
        
        //获取短信日志content
        $content = str_replace($find,$replace,$content);
        
        //检查手机号状态
        if($this->checkBlackTel($sendArr["ship_mobile"])){
            return $this->o2oSendOne($sendType,$sendArr,$content,true);
        }else{
            $this->writeSmslog($sendArr["ship_mobile"],$content,'该手机号处于免打扰列表中',0);
        }
    }
    
    //门店发送短信操作
    public function o2oSendOne($sendType,$sendArr,$content,$echostr=false){
        base_kvstore::instance('taoexlib')->fetch('account', $account);
        if (!unserialize($account)) {
            return false;
        }
        $param = unserialize($account);
        $info = taoexlib_utils::get_user_info($param);
        if ('succ' == $info->res) {
            if ($info->info->month_residual) {
                
                //门店自提
                if($sendType == "pickUpInStore"){
                    $arr_replace = array(
                        'ship_name' => $sendArr["ship_name"],
                        'pickup_bn' => $sendArr["pickup_bn"],
                        'pickup_code' => $sendArr["pickup_code"],
                        'store_name' => $sendArr["store_name"],
                        'msgsign' => "【".$sendArr["store_name"]."】",
                        'store_addr' => $sendArr["store_addr"],
                        'store_contact_tel' => $sendArr["store_contact_tel"],
                    );
                }
                
                //门店配送
                if($sendType == "storeDelivery"){
                    $arr_replace = array(
                            'ship_name' => $sendArr["ship_name"],
                            'store_name' => $sendArr["store_name"],
                            'msgsign' => "【".$sendArr["store_name"]."】",
                            'store_contact_tel' => $sendArr["store_contact_tel"],
                    );
                }
    
                $mscontent =array(
                        'phones' => $sendArr["ship_mobile"],
                        'replace' => $arr_replace,
                        'tplid'  =>$sendArr["tplid"],
                        'content'=>$content,
                );
    
                $smsresult=taoexlib_utils::send_notice($param, $mscontent);
    
                if($echostr&&$smsresult)
                    return 'sendOk';
                else
                    return 'sendFalse';
            }else{
                $this->writeSmslog($phone,$content['content'],'当前没有可用的短信条数！',0);
                if($echostr) return 'month_residual_zero';
            }
        }else{
            $this->writeSmslog($phone,$content,$info->info,0);
            if($echostr) return '发送失败，原因：'.$info->info.'！';
        }
    }

    /*
    * 发货并且发短信提醒
    */
    public function deliverySendMessage($logi_no){
        $switch=app::get("taoexlib")->getConf('taoexlib.message.switch');
        if($switch == 'on'){
            $info = $this->getLogiNoInfo($logi_no);
            if($info){
                //$phone = trim($info[1][12]);
                //$delivery_bn = $info[1][11];
                $phone = $info['replace']['ship_mobile'];
                $delivery_bn = $info['replace']['delivery_bn'];
                $messcontent = $info['content'];
                if(!empty($phone)){
                    if($this->checkBlackTel($phone)){
                        
                        $this->sendOne($phone,$info,$logi_no,$delivery_bn);
                    }else{
                        $this->writeSmslog($phone,$messcontent,'该手机号处于免打扰列表中',0);
                    }
                }
            }
        }
    }

    /*
     * 通过物流单号 获取信息
     * 如果是false 说明系统中没有查找的快递单号
     * 1,和短信设置中的信息匹配替换
     * 2,将获得的数据一并返回 方便后面短信发送需要提取个性化信息
     */
     public function getLogiNoInfo($logi_no, $content=NULL){
        $deliveryinfo = app::get('ome')->model('delivery');
        $rule_sample_mdl = app::get('taoexlib')->model('sms_bind');
        
        $info = $deliveryinfo->dump(array('logi_no|nequal' => $logi_no),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*'),'shop'=>array('*')));
        
        //物流单号系统中不存在
        if(empty($info)){ return false; }

        if(!$content){
            $contentinfo = $rule_sample_mdl->getSmsContentByRuleId($info['sms_group']);

            if (!$contentinfo['content']) {
                return false;
            }else{
                $content = $contentinfo['content'];
            }
        }else{

        }
        //获取匹配信息区域

        //《发货单号$delivery_bn》
        $delivery_bn=$info["delivery_bn"];

        //《订单号$orderstr》如果多个订单号使用逗号隔开
        $ordersObj = app::get('ome')->model('orders');
        $order_id = array_keys($info['delivery_order']);
        $orders = $ordersObj->getList('order_id, order_bn, status, ship_status, process_status,total_amount,payed', array('order_id|in' => $order_id));
        $i = 0;
        foreach ($orders as $os) {
            if($i>0){ $orderstr .=','; }
            $orderstr .=$os['order_bn'];
            $i++;
            //《订单金额$total_amount》
            $total_amount += $os['total_amount'];
            //《实际付款总金额$payed》
            $payed += $os['payed'];
        }
                    
        //《订单优惠金额$cheap》
        $cheap = $total_amount-$payed;
                    
        //店铺信息:
        $shopinfo = app::get('ome')->model('shop');
        $shopinfoarr = $shopinfo->dump(array('shop_id|nequal' => $info['shop_id']),'*');
        
        //《店铺名$shopname》
        $shopname = $shopinfoarr['name'];
                    
        //会员信息
        $membersinfo = app::get('ome')->model('members');
        $membersinfoarr = $membersinfo->dump(array('member_id|nequal' => $info['member_id']),'*');
        
        //《会员名$uname》
        $uname = $membersinfoarr['account']['uname'];
                    
        //《物流费用$logi_actual》
        $logi_actual = $info['delivery_cost_actual'];
        if($logi_actual == '0'){
            $logi_actual='包邮';
        }
                    
        //《收货人$ship_name》
        $ship_name = $info['consignee']['name'];
        
        //《收货人手机号码$ship_mobile》
        $ship_mobile = $info['consignee']['mobile'];
                    
        //《物流公司$logi_name》
        $logi_name = $info['logi_name'];
                    
        //《物流单号$logi_no》
        $logi_no = $info['logi_no'];
                    
        //《发货时间$delivery_time》
        $delivery_time = date("d日 H点i分",$info['delivery_time']);
        //订单创建时间
        $create_time   = date("d日 H点i分",$info['create_time']);        
        //$find 和 $replace 一一对应，需要增加删除修改，修改对应的做改动
        $find = array('{会员名}','{收货人}','{店铺名称}','{物流公司}','{物流单号}','{发货时间}','{配送费用}','{订单号}','{订单金额}','{付款金额}','{订单优惠}','{发货单号}','{收货人手机号}','{订单时间}','{短信签名}');
        $replace = array($uname,$ship_name,$shopname,$logi_name,$logi_no,$delivery_time,$logi_actual,$orderstr,$total_amount,$payed,$cheap,$delivery_bn,$ship_mobile,$create_time,$msgsign);
                    
        //$content为短信配置中的模板信息
       //$content = $this->app->getConf('taoexlib.message.samplecontent');
        $messcontent['tplid'] = $contentinfo['tplid'];
        $messcontent['replace'] = array(
            'uname' =>$uname,
            'ship_name'   =>$ship_name,
            'shopname'=>$shopname,
            'logi_name'=>$logi_name,
            'logi_no'=>$logi_no,
            'delivery_time'=>$delivery_time,
            'logi_actual'=>$logi_actual,
            'orderstr'=>$orderstr,
            'total_amount'=>$total_amount,
            'payed'=>$payed,
            'cheap'=>$cheap,
            'delivery_bn'=>$delivery_bn,
            'ship_mobile'=>$ship_mobile,
            'create_time'=>$create_time,
            'msgsign'=>"【".$shopname."】",
        );
        
        //将获取的值和模板中的定义的变量替换
        $messcontent['content'] = str_replace($find,$replace,$content);
        //组合数组:为了获取个别信息做准备 $messarr[0]：为组合的数据 $messarr[1][0...9]:为个别数据
        //$messarr[] = $messcontent;
        //$messarr[] = $replace;            
        //返回给ajax成功
        return $messcontent;

     }

    /*
     * 检测是否在免打扰手机号列表中
     * 将手机号放进去验证，检查该手机号是否处于免打扰列表中
     */
     public function checkBlackTel($tel){
        $blacklist=app::get('taoexlib')->getConf("taoexlib.message.blacklist");
        $blarr=explode("##",$blacklist);
        if(!in_array($tel,$blarr)){
            return true;
        }else{
            return false;
        }
     }

    /*
    * sendOne:发送短信
    * @param $phone='13838385438'
    * @param $content string;
    * @param $echostr 是否开启输出功能 预览的时候开启返回短信状态信息 关闭将不再显示短信状态信息 用于发货 可以到短信日志查看日志状态信息
    */
    public function sendOne($phone,$content,$logi_no,$delivery_bn,$echostr=false) {
        base_kvstore::instance('taoexlib')->fetch('account', $account);
         if (!unserialize($account)) { return false; }
        $param = unserialize($account);
        $info = taoexlib_utils::get_user_info($param);
        //短信签名验证
//        preg_match('/\【(.*?)\】$/',$content['content'],$filtcontent1);
//        if ($filtcontent1) {
//            kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$filtcontent1[0]));
//        }
        if ('succ' == $info->res) {
            if ($info->info->month_residual) {
                $mscontent =array(
                  'phones' => $phone,
                  'replace' => $content['replace'],
                  'tplid'  =>$content['tplid'],
                  'content'=>$content['content'],
                );

                $smsresult=taoexlib_utils::send_notice($param, $mscontent);
                
                if($echostr&&$smsresult)
                    return 'sendOk';
                else
                    return 'sendFalse';
            }else{
                $this->writeSmslog($phone,$content['content'],'当前没有可用的短信条数！',0);
                
                if($echostr) return 'month_residual_zero';
            }
        }else{
            $this->writeSmslog($phone,$content,$info->info,0);

            if($echostr) return '发送失败，原因：'.$info->info.'！';
        }
    }

    /*
    * wujian@shopex.cn
    * 短信日志
    * 2012年2月21日
    * @param $phonearr 电话号码
    * @param $delivery_bn 发货单号
    * @param $logo 快递单号
    * @param $content 发送内容
    * @param $msg 短信状态信息
    * @param $status 短信状态
    */
    public function writeSmslog($phone,$content,$msg,$status){
        $messlog = app::get('taoexlib')->model("log");
        $messlogdata = array(
            'mobile'=>$phone,
            'batchno'=>'',
            'content'=>$content,
            'sendtime'=>time(),
            'msg'=>$msg,
            'status' =>$status,
        );

        $messlog->insert($messlogdata);		
    }

    /**
     * 完成发货后的短信动作
     *
     * @return void
     * @author 
     **/
    public function sendmsgAfterConsign($delivery_id)
    {
        base_kvstore::instance('taoexlib')->fetch('account', $account);
        if (!$account || !unserialize($account)) return 'sendFalse';

        $delivery = app::get('ome')->model('delivery')->getFinishDelivery($delivery_id);

        if (!$this->validBeforeSend($delivery,$errmsg)){

            return 'sendFalse';
        }

        $row = app::get('taoexlib')->model('sms_bind')->dump(array('send_type'=>'delivery'),'bind_id');
        if (!$row) return 'sendFalse';

        $smslog = array(
            'obj_id'     => $delivery_id,
            'obj_type'   => 'consign',
            'createtime' => time(),
        );

        $insert_id = app::get('taoexlib')->model('smslog')->insert($smslog);

        $this->__push_mq($insert_id);

        return true;
        // $contentinfo = app::get('taoexlib')->model('sms_bind')->getSmsContentByRuleId($delivery['sms_group'],'1');

        // if (!$contentinfo) return 'sendFalse';

        // $info = $this->getSms($delivery,$contentinfo);

        // return $this->sendOne($delivery['ship_mobile'],$info,$delivery['logi_no'],$delivery['delivery_bn']);
    }

    public function sms_retry($smslog_id)
    {
        $smslogModel = app::get('taoexlib')->model('smslog');

        $affect_rows = $smslogModel->update(array('status'=>'0'),array('id'=>$smslog_id,'status'=>'2'));

        if ($affect_rows === 1) {
            $this->__push_mq($smslog_id);
        }

        return $affect_rows===1 ? true : false;
    }

    private function __push_mq($smslog_id)
    {
        if (defined('SAAS_AFTERCONSIGN_MQ') && SAAS_AFTERCONSIGN_MQ == 'true') {
            $queue_data = array(
                'id'        => $smslog_id, 
                'task_type' => 'sms',
                'uniqid'    => 'sms_'.$smslog_id,
            );

            taskmgr_func::multiQueue($GLOBALS['_MQ_AFTERCONSIGN_CONFIG'], 'TG_AFTERCONSIGN_EXCHANGE', 'TG_AFTERCONSIGN_QUEUE','tg.sys.sms.*',$queue_data);
        }
    }

    /**
     * 发送前的验证
     *
     * @return bool
     * @author 
     **/
    public function validBeforeSend($delivery,&$errmsg)
    {
        // 验证短信是否开启
        //短信设置界面隐藏默认发货同时短信提醒为开启
        /*if ('on' != app::get("taoexlib")->getConf('taoexlib.message.switch')){
            $errmsg = 'not open';
            return false;
        }*/
        // 验证手机
        if (!$delivery['ship_mobile']) {
            $errmsg = 'no mobile';
            return false;
        }
        // 是否拉黑
        if (!$this->checkBlackTel($delivery['ship_mobile'])){
            $errmsg = 'black mobile';
            return false;
        }
        return true;
    }


}