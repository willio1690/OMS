<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票公共类
 *
 * @author wangjianjun<wangjianjun@shopex.cn>
 * @version 0.1
 */
class invoice_common{
    
    //通过order_id获取对应的发票信息
    public function getInvoiceInfoByOrderId($order_id,$type="all",$limit_one=true){
        if(!$order_id){
            return false;
        }
        $mdlInOrder = app::get('invoice')->model('order');
        $arr_filter = array('order_id'=>$order_id);
        //未作废（即未开票/已开票）
        if($type == 'effective'){
            $arr_filter["is_status|in"] = array("0","1");
        }
        //是否限制获取一条记录
        if($limit_one){
            $rs_info = $mdlInOrder->getList('*', $arr_filter, 0, 1, 'id DESC');
        }else{
            $rs_info = $mdlInOrder->getList('*', $arr_filter);
        }
        
        if(empty($rs_info)){
            return false;
        }
        return $rs_info;
    }
    
    //依据is_status获取对应的文字描述
    public function getIsStatusText($is_status){
        switch ($is_status){
            case '0':
                $return_text = '未开票';
                break;
            case '1':
                $return_text = '已开票';
                break;
            case '2':
                $return_text = '已作废';
                break;
        }
        return $return_text;
    }
    
    //依据mode获取对应的文字描述
    public function getModeText($mode){
        switch ($mode){
            case '0':
                $return_text = '纸质发票';
                break;
            case '1':
                $return_text = '电子发票';
                break;
        }
        return $return_text;
    }
    
    //通过shop_id获取当前店铺发票配置信息
    public function getInvoiceOrderSet($shop_id){
        if(!$shop_id){
            return false;
        }
        $mdlInOrderSet = app::get('invoice')->model('order_setting');
        $rs_setting = $mdlInOrderSet->dump(array("shop_id"=>$shop_id));
        if($rs_setting){
            return $rs_setting;
        }else{
            return false;
        }
    }
    
    //获取发票内容列表
    public function getInvoiceContent(){
        $mdlInContent = app::get('invoice')->model('content');
        $rs_invoice_content = $mdlInContent->getList();
        return $rs_invoice_content;
    }
    
    //u id获取u name
    function getUserNameByUserID($uid){
        if(!$uid){
            return false;
        }
        $filter = array('user_id'=>intval($uid));
        $rows = app::get('desktop')->model('users')->dump($filter,'user_id, name');
        return $rows;
    }
    
    //打电子发票接口 获取shop_type 判断是否是天猫店
    function returnEinvoiceShopType($shop_info){
        if(strtoupper($shop_info["tbbusiness_type"]) == "B" && $shop_info["shop_type"] == "taobao"){
            //天猫
            return "tmall";
        }else{
            return $shop_info["shop_type"];
        }
    }
    
    //打电子发票接口需要：通过shop_type映射出电商平台代码
    function getPlatformByShopType($shop_type){
        $platform_list = array(
            "taobao" => "TB",
            "tmall" => "TM",
            "360buy" => "JD",
            "dangdang" => "DD",
            "paipai" => "PP",
            "qq_buy" => "QQ",
            "amazon" => "AMAZON",
            "suning" => "SN",
            "gome" => "GM",
            "guomei" => "GM",
            "vop" => "WPH",
            "mogujie" => "MGJ",
            "yintai" => "YT",
            "yihaodian" => "YHD",
            "vjia" => "VANCL",
            "alibaba" => "1688",
        );
        $platform_code = $platform_list[$shop_type];
        if(!$platform_code && $shop_type){
            $platform_code = "OTHER";
        }
        return $platform_code;
    }
    
    
    //taobao系 获取上传天猫的link
    function get_upload_link($id,$shop_id,$order_id,$finder_id,$billing_type){
        //获取shop_type
        $mdlOmeShop = app::get('ome')->model('shop');
        $rs_shop = $mdlOmeShop->dump(array("shop_id"=>$shop_id));
        //不是淘宝系订单 不显示上传link
        if($rs_shop["shop_type"] != "taobao"){
            return;
        }
        //判断当前红/蓝票是否已经上传
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $rs_item = $mdlInOrderElIt->dump(array("id"=>$id,"billing_type"=>$billing_type));
        if($rs_item["upload_tmall_status"] == "2"){
            return;
        }
        //打prepare接口 区分link 并且现不用再判断是蓝是红 不用再判断返回link的条件 外层已判
//         $doUploadTmall_link = sprintf('<a href="javascript:if (confirm(\'上传电子发票数据给天猫，天猫给消费者提供下载功能？\')){W.page(\'index.php?app=invoice&ctl=admin_order&act=uploadTmallExpire&id=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">上传</a>', $id, $finder_id);
        $uploadTmall_link_first_blue = '<a href="index.php?app=invoice&ctl=admin_order&act=uploadTmallExpire&id='.$id.'&billing_type=1&invoice_action_type=1&finder_id='.$finder_id.'" target="dialog::{width:600,height:225,title:\'上传天猫\'}">上传</a>';
        $uploadTmall_link_other = '<a href="index.php?app=invoice&ctl=admin_order&act=uploadTmallExpire&id='.$id.'&billing_type='.$billing_type.'&finder_id='.$finder_id.'" target="dialog::{width:600,height:225,title:\'上传天猫\'}">上传</a>';
        //是否是第一次开蓝票 where条件说明：理论上如果存在这样的发票记录肯定sync是6冲红成功 这里大于2必定要开蓝成功
        $mdlInOrder = app::get('invoice')->model('order');
        $same_order_invoice = $mdlInOrder->dump(array("id|noequal"=>$id,"order_id"=>$order_id,"mode"=>1,"sync|than"=>2));
        if(empty($same_order_invoice) && $billing_type == 1){
            return $uploadTmall_link_first_blue; //第一次开蓝票 不用选择 选择 作废或重开原因 直接上传
        }else{
            return $uploadTmall_link_other;
        }
    }


    //通过shop_id获取当前店铺发票配置信息
    public function getInOrderSetByShopId($shop_id,$mode){
        if(!$shop_id){
            return false;
        }
        $mdlInSetShopIdRel = app::get('invoice')->model('setting_shopid_relation');
        $mdlInOrderSet = app::get('invoice')->model('order_setting');
        //通过关系表获取开票配置的主键sid
        $rs_rel = $mdlInSetShopIdRel->dump(array("shop_id"=>$shop_id));
        if(empty($rs_rel)){
            return false;
        }
        //获取开启状态下的开票信息(纸质mode为0和电子mode为1)
        $rs_setting = $mdlInOrderSet->dump(array("sid"=>$rs_rel["sid"],"status"=>"true","mode"=>$mode));
        if($rs_setting){
            return $rs_setting;
        }else{
            return false;
        }
    }
}