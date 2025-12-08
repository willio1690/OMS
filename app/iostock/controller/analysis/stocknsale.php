<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 呆滞库存统计报表
 *
 * @category iostock
 * @package iostock/constroller/analysis
 * @author wangjianjun<wangjianjun@shopex.cn>
 * @version $Id: stocknsale.php 2015-11-3 10:51Z
 */
class iostock_ctl_analysis_stocknsale extends desktop_controller{
    
    /**
     * index
     * @return mixed 返回值
     */

    public function index(){
		
		$post_filter=array();
		
		foreach ($_POST as $key => &$val) {
			if((in_array($key,array("branch_id","material_type")) && intval($val)<=0) || $val === ""){
				unset($_POST[$key]);
			}else{
				$post_filter[$key] = $val;
			}
		}
		
		kernel::single('iostock_stocknsale')->set_params($post_filter)->display();
    
	}
	
	function set_nsale_day(){
		
			$this->pagedata["set_nsale_day_title"] = "呆滞库存天数定义";
			$this->pagedata["set_nsale_day_help_message"] = "设置呆滞库存的天数和货品销售出库的情况密切相关";
			$this->pagedata["set_nsale_day_form_action"] = "index.php?app=iostock&ctl=analysis_stocknsale&act=do_set_nsale_day";
			$arr_select_data = array(
					"90" => "90",
					"180" => "180",
					"365" => "365",
			);
			$this->pagedata["select_data"] = $arr_select_data;
			$set_byself_value = "99999";
			$this->pagedata["select_data"][$set_byself_value] = "自定义";
			
			$iostock_app=app::get("iostock");
			$nsale_days = $iostock_app->getConf("report_stock_nsale_days");
			
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
				
		$iostock_app=app::get("iostock");
		$iostock_app->setConf("report_stock_nsale_days", $set_value);
		
		$this->end(true,'修改完成');
	}

}