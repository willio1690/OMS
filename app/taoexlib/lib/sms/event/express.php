<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @description 发货揽收短信触发事件
 * @access public
 * @param void
 * @return void
 */
class  taoexlib_sms_event_express extends taoexlib_sms_event_abstract implements taoexlib_sms_event_interface
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
    protected $_tmplContent = '{收货人}，您好！您在{店铺名称}订购的商品,{物流公司}已揽收，单号：{物流单号}，谢谢！{短信签名}';

    /**
     * 
     * 短信模板变量
     * @var array
     */

    protected function getTmplVariables(){
        return array(

            array('id' => 'shouhuoren', 'name' => '收&nbsp;&nbsp;货&nbsp;&nbsp;人', 'value' => '{收货人}','br'=>'<br>'),

            array('id' => 'wuliugongsi', 'name' => '物流公司', 'value' => '{物流公司}','br'=>'<br>'),
            array('id' => 'wuliudanhao', 'name' => '物流单号', 'value' => '{物流单号}','br'=>''),

            array('id'=>'msgsign','name'=>'短信签名','value'=>'{短信签名}'),
            array('id' => 'recovery', 'name' => '<font style="color:#f00">恢复默认</font>', 'value' => $this->_tmplContent,'br'=>'<br>','img'=>kernel::base_url(1).'/app/desktop/statics/bundle/afresh.gif'),
        );
    }

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
        $contentinfo = $rule_sample_mdl->getOtherSmsContent('express');
        if (!$contentinfo['content']) {
            $error_msg = '短信模板未设置或未审核通过';
            return false;
        }else{
            $content = $contentinfo['content'];
        }

        //获取匹配信息区域

        //《发货单号$delivery_bn》
        $delivery_bn=$info["delivery_bn"];

        //店铺信息:
        $shopinfo = app::get('ome')->model('shop');
        $shopinfoarr = $shopinfo->dump(array('shop_id|nequal' => $info['shop_id']),'*');

        //《店铺名$shopname》
        $shopname = $shopinfoarr['name'];

        //《收货人$ship_name》
        $ship_name = $info['consignee']['name'];

        //《收货人手机号码$ship_mobile》
        $ship_mobile = $info['consignee']['mobile'];

        //《物流公司$logi_name》
        $logi_name = $info['logi_name'];

        //《物流单号$logi_no》
        $logi_no = $info['logi_no'];


        //短信签名
        $msgsign = "【".$shopname."】";
        $helper = kernel::single('base_view_helper');
        //$find 和 $replace 一一对应，需要增加删除修改，修改对应的做改动
        $find = array('{收货人}','{店铺名称}','{物流公司}','{物流单号}','{收货人手机号}','{短信签名}');
        $replace = array($helper->str_mosaic($ship_name,'*',true,0,1),$shopname,$logi_name,$helper->str_mosaic($logi_no,'',false,0,0,-4),$ship_mobile,$msgsign);

        //$content为短信配置中的模板信息
        //$content = $this->app->getConf('taoexlib.message.samplecontent');
        $sendParams['phones'] = $ship_mobile;
        $sendParams['tplid'] = $contentinfo['tplid'];
        $sendParams['replace'] = array(

            'ship_name'   =>$helper->str_mosaic($ship_name,'*',true,0,1),
            'shopname'=>$shopname,
            'logi_name'=>$logi_name,
            'logi_no'=>$logi_name,$helper->str_mosaic($logi_no,'',false,0,0,-4),



            'ship_mobile'=>$ship_mobile,

            'msgsign'=>$msgsign,
        );

        //将获取的值和模板中的定义的变量替换
        $sendParams['content'] = str_replace($find,$replace,$content);
        //组合数组:为了获取个别信息做准备 $messarr[0]：为组合的数据 $messarr[1][0...9]:为个别数据
        //$messarr[] = $messcontent;
        //$messarr[] = $replace;
        //返回给ajax成功
        return true;
    }
}