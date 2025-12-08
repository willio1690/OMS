<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 不动销商品报表
 *
 * @category omeanalysts
 * @package omeanalysts/constroller/analysis
 * @author wangjianjun<wangjianjun@shopex.cn>
 * @version $Id: productnsale.php 2015-11-3 10:51Z
 */
class omeanalysts_ctl_analysis_productnsale extends desktop_controller{
    
    /**
     * index
     * @return mixed 返回值
     */

    public function index(){
        $post_filter=array();
        foreach ($_POST as $key => &$val) {
            if(($key=="material_type" && intval($val)<=0) || $val === ""){
                unset($_POST[$key]);
            }else{
                $post_filter[$key] = $val;
            }
        }

        kernel::single('omeanalysts_productnsale')->set_params($post_filter)->display();
    }
	
    function set_nsale_day(){
        $this->pagedata["set_nsale_day_title"] = "不动销库存天数定义";
        $this->pagedata["set_nsale_day_help_message"] = "设置不动销库存的天数和货品销售出库的情况密切相关";
        $this->pagedata["set_nsale_day_form_action"] = "index.php?app=omeanalysts&ctl=analysis_productnsale&act=do_set_nsale_day";
        $arr_select_data = array(
                "90" => "90",
                "180" => "180",
                "365" => "365",
        );
        $this->pagedata["select_data"] = $arr_select_data;
        $set_byself_value = "99999";
        $this->pagedata["select_data"][$set_byself_value] = "自定义";

        $omeanalysts_app=app::get("omeanalysts");
        $nsale_days = $omeanalysts_app->getConf("report_product_nsale_days");

        if(in_array($nsale_days,$arr_select_data)){
            $this->pagedata["part_select_value"] = "choose_select";
            $this->pagedata["selected_value"] = $nsale_days;
        }else{
            $this->pagedata["part_select_value"] = "choose_input";
            $this->pagedata["selected_value"] = $set_byself_value;
            $this->pagedata["nsale_day_byself"] = $nsale_days;
        }

        $this->display('analysis/set_nsale_day.html');
    }
	
    function do_set_nsale_day(){
        $this->begin();
        $data = $_POST;
        switch ($data["part_select"]){
            case "choose_select":
                $set_value = $data["nsale_day_select"];
                break;
            case "choose_input":
                $set_value = $data["nsale_day_input"];
                break;
        }

        $omeanalysts_app=app::get("omeanalysts");
        $omeanalysts_app->setConf("report_product_nsale_days", $set_value);
        $this->end(true,'修改完成');
    }

}