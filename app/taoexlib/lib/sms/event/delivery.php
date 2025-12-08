<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @description 线上发货短信触发事件
 * @access public
 * @param void
 * @return void
 */
class  taoexlib_sms_event_delivery extends taoexlib_sms_event_abstract implements taoexlib_sms_event_interface
{

    /**
     * 
     * 短信模板标题
     * @var string
     */
    protected $_tmplTitle = '发货提醒';

    /**
     * 
     * 短信模板内容
     * @var string
     */
    protected $_tmplContent = '{收货人}，您好！您在{店铺名称}订购的商品已通过{物流公司}发出，单号：{物流单号},请当面检查后再签收，谢谢！{短信签名}';

    /**
     * 
     * 短信模板变量
     * @var array
     */

    protected function getTmplVariables(){
        return array(
            array('id' => 'huiyuan', 'name' => '会&nbsp;&nbsp;员&nbsp;&nbsp;名', 'value' => '{会员名}','br'=>''),
            array('id' => 'shouhuoren', 'name' => '收&nbsp;&nbsp;货&nbsp;&nbsp;人', 'value' => '{收货人}','br'=>'<br>'),
            array('id' => 'dingdanhao', 'name' => '订&nbsp;&nbsp;单&nbsp;&nbsp;号', 'value' => '{订单号}','br'=>''),
            array('id' => 'shouhuodizhi', 'name' => '收货地址', 'value' => '{收货地址}','br'=>'<br>'),
            array('id' => 'peisongfeiyong', 'name' => '配送费用', 'value' => '{配送费用}','br'=>''),
            array('id' => 'wuliugongsi', 'name' => '物流公司', 'value' => '{物流公司}','br'=>'<br>'),
            array('id' => 'wuliudanhao', 'name' => '物流单号', 'value' => '{物流单号}','br'=>''),
            array('id' => 'fukuanjine', 'name' => '付款金额', 'value' => '{付款金额}','br'=>'<br>'),
            array('id' => 'dingdanjine', 'name' => '订单金额', 'value' => '{订单金额}','br'=>''),
            array('id' => 'dingdanyouhui', 'name' => '订单优惠', 'value' => '{订单优惠}','br'=>'<br>'),
            array('id' => 'fahuoshijian', 'name' => '发货时间', 'value' => '{发货时间}','br'=>''),
            array('id' => 'dingdanshijian', 'name' => '订单时间', 'value' => '{订单时间}','br'=>'<br>'),
            array('id' => 'fenjihao', 'name' => '分机号', 'value' => '{分机号}','br'=>'<br>'),
            array('id'=>'msgsign','name'=>'短信签名','value'=>'{短信签名}'),
            array('id' => 'recovery', 'name' => '<font style="color:#f00">恢复默认</font>', 'value' => $this->_tmplContent,'br'=>'<br>','img'=>kernel::base_url(1).'/app/desktop/statics/bundle/afresh.gif'),
        );
    }

    /**
     * 检查Params
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回验证结果
     */
    public function checkParams(&$params, &$error_msg){

        if(!isset($params['delivery_id']) || empty($params['delivery_id'])){
            $error_msg = '发货单不能为空';
            return false;
        }

        return true;
    }

    /**
     * formatContent
     * @param mixed $params 参数
     * @param mixed $sendParams 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function formatContent($params, &$sendParams, &$error_msg){
        return $this->getLogiNoInfo($params['delivery_id'], $sendParams, $error_msg);
    }

    /*
     * 通过物流单号 获取信息
     * 如果是false 说明系统中没有查找的快递单号
     * 1,和短信设置中的信息匹配替换
     * 2,将获得的数据一并返回 方便后面短信发送需要提取个性化信息
     */
     private function getLogiNoInfo($delivery_id, &$sendParams, &$error_msg){
        $deliveryinfo = app::get('ome')->model('delivery');
        $rule_sample_mdl = app::get('taoexlib')->model('sms_bind');
        
        $info = $deliveryinfo->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*'),'shop'=>array('*')));
        //物流单号系统中不存在
        if(empty($info)){
            $error_msg = '运单号不存在';
            return false;
        }

        //获取线上发货短信模板
        $contentinfo = $rule_sample_mdl->getSmsContentByRuleId($info['sms_group']);
        if (!$contentinfo['content']) {
            $error_msg = '短信模板未设置或未审核通过';
            return false;
        }else{
            $content = $contentinfo['content'];
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
         $helper = kernel::single('base_view_helper');
         $orders = explode(',',$orderstr);
         $orderBns = array();
         if ($orders) {
             foreach ($orders as $v) {
                 $orderBns[] = $helper->str_mosaic($v, '*', true, 2, 4);
             }
             $orderBns = implode(',',$orderBns);
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
        //短信签名
        $msgsign = "【".$shopname."】";

        //$find 和 $replace 一一对应，需要增加删除修改，修改对应的做改动
        $find = array('{会员名}','{收货人}','{店铺名称}','{物流公司}','{物流单号}','{发货时间}','{配送费用}','{订单号}','{订单金额}','{付款金额}','{订单优惠}','{发货单号}','{收货人手机号}','{订单时间}','{短信签名}','{分机号}');
        $replace = array($helper->str_mosaic($uname,'*',true,0,1),$helper->str_mosaic($ship_name,'*',true,0,1),$shopname,$logi_name,$helper->str_mosaic($logi_no,'',false,0,0,-4),$delivery_time,$logi_actual,$orderBns,$total_amount,$payed,$cheap,$delivery_bn,$ship_mobile,$create_time,$msgsign,'');
                    
        //$content为短信配置中的模板信息
       //$content = $this->app->getConf('taoexlib.message.samplecontent');
        $sendParams['phones'] = $ship_mobile;
        $sendParams['order_bn'] = $orderstr;
        $sendParams['tplid'] = $contentinfo['tplid'];
         $sendParams['replace'] = array(
             'uname'         => $helper->str_mosaic($uname, '*', true, 0, 1),
             'ship_name'     => $helper->str_mosaic($ship_name, '*', true, 0, 1),
             'shopname'      => $shopname,
             'logi_name'     => $logi_name,
             'logi_no'       => $helper->str_mosaic($logi_no, '', false, 0, 0, -4),
             'delivery_time' => $delivery_time,
             'logi_actual'   => $logi_actual,
             'orderstr'      => $orderBns,
             'total_amount'  => $total_amount,
             'payed'         => $payed,
             'cheap'         => $cheap,
             'delivery_bn'   => $delivery_bn,
             'ship_mobile'   => $ship_mobile,
             'create_time'   => $create_time,
             'msgsign'       => $msgsign,
             'fenjihao'      => ''
         );
         // 判断手机加密
         $is_encrypt = kernel::single('ome_security_router',$info['shop_type'])->is_encrypt(array('ship_mobile'=>$ship_mobile),'delivery');
         if ($is_encrypt) {
             $sendParams['is_encrypt']   = $is_encrypt;
             $sendParams['shop_type']    = $info['shop_type'];
             $sendParams['s_node_id']    = $shopinfoarr['node_id'];
         }
        //将获取的值和模板中的定义的变量替换
        $sendParams['content'] = str_replace($find,$replace,$content);
        //组合数组:为了获取个别信息做准备 $messarr[0]：为组合的数据 $messarr[1][0...9]:为个别数据
        //$messarr[] = $messcontent;
        //$messarr[] = $replace;            
        //返回给ajax成功
        return true;
     }
}