<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @description 门店自提短信触发事件
 * @access public
 * @param void
 * @return void
 */
class  taoexlib_sms_event_o2opickup extends taoexlib_sms_event_abstract implements taoexlib_sms_event_interface
{

    /**
     *
     * 短信模板标题
     * @var string
     */
    protected $_tmplTitle = '门店自提提醒';

    /**
     *
     * 短信模板内容
     * @var string
     */
    protected $_tmplContent = '尊敬的用户{收货人}，您的包裹已到达位于{门店地址}的{门店名称}，门店联系电话{门店联系电话}，请凭借提货单{提货单}和校验码{校验码}进行自提，谢谢！{短信签名}';

    /**
     *
     * 短信模板变量
     * @var array
     */
    protected function getTmplVariables(){
        return array(
            array('id' => 'shouhuoren', 'name' => '收&nbsp;&nbsp;获&nbsp;&nbsp;人', 'value' => '{收货人}','br'=>''),
            array('id' => 'tihuodan', 'name' => '提&nbsp;&nbsp;货&nbsp;&nbsp;单', 'value' => '{提货单}','br'=>'<br>'),
            array('id' => 'jiaoyanma', 'name' => '校&nbsp;&nbsp;验&nbsp;&nbsp;码', 'value' => '{校验码}','br'=>''),
            array('id' => 'mendianmingcheng', 'name' => '门店名称', 'value' => '{门店名称}','br'=>'<br>'),
            array('id' => 'mendiandizhi', 'name' => '门店地址', 'value' => '{门店地址}','br'=>''),
            array('id' => 'mendianlianxidianhua', 'name' => '门店联系电话', 'value' => '{门店联系电话}','br'=>'<br>'),
            array('id'=>'msgsign','name'=>'短信签名','value'=>'{短信签名}'),
            array('id' => 'recovery', 'name' => '<font style="color:#f00">恢复默认</font>', 'value' => $this->_tmplContent,'br'=>'<br>','img'=>kernel::base_url(1).'/app/desktop/statics/bundle/afresh.gif'),
        );
    }

    public function checkParams(&$params, &$error_msg){

        if(!$params["ship_mobile"] || !$params["ship_name"] || !$params["store_name"]  || !$params["store_contact_tel"] || !$params["pickup_bn"] || !$params["store_addr"]){
            $error_msg = '收货人/联系电话/门店名称/门店电话/门店地址/提货单不能为空';
            return false;
        }

        return true;
    }

    public function formatContent($params, &$sendParams, &$error_msg){
        //获取短信模板内容
        $rule_sample_mdl = app::get('taoexlib')->model('sms_bind');
        if(!$content){
            $contentinfo = $rule_sample_mdl->getOtherSmsContent('o2opickup');
            if (!$contentinfo['content']) {
                $error_msg = '短信模板未设置或未审核通过';
                return false;
            }else{
                $content = $contentinfo['content'];
                $sendParams["tplid"] = $contentinfo['tplid'];
            }
        }

        //临时变量
        $ship_name = $params['ship_name'];
        $store_name = $params['store_name'];
        $pickup_bn = $params['pickup_bn'];
        $store_addr = $params['store_addr'];
        $store_contact_tel = $params['store_contact_tel'];

        //在关闭销单校验码状态下 不生成校验码 所以在发送日志和手机上收到的校验码显示“无”
        $pickup_code = $params['pickup_code'];
        $mobile_pkcode_display = '******';
        if (!$pickup_code){
            $pickup_code = "无";
            $mobile_pkcode_display = $pickup_code;
        }
        $helper = kernel::single('base_view_helper');
        $sendParams['phones'] = $params["ship_mobile"];
        $sendParams['replace'] = array(
            'ship_name' => $helper->str_mosaic($ship_name,'*',true,0,1),
            'pickup_bn' => $pickup_bn,
            'pickup_code' => $pickup_code,
            'store_name' => $store_name,
            'msgsign' => "【".$store_name."】",
            'store_addr' => $store_addr,
            'store_contact_tel' => $store_contact_tel,
        );
    
        //替换签名 获取完整短信日志content
        $find = array('{收货人}','{提货单}','{校验码}','{门店名称}','{短信签名}','{门店地址}','{门店联系电话}');
        $replace = array($helper->str_mosaic($ship_name,'*',true,0,1),'******',$mobile_pkcode_display, $store_name, $sendParams['replace']['msgsign'], $store_addr, $store_contact_tel);

        //获取短信日志content
        $sendParams['content'] = str_replace($find,$replace,$content);

        return true;
    }
}