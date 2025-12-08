<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_analysis_productnsale extends dbeav_model{
	
	var $has_export_cnf = true;
	var $export_name = '不动销商品报表';
	
	var $defaultOrder = array('nsale_days',' desc');
	
	private $material_type_arr= array("1" => "成品","2" => "半成品");
	
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
		
		//for 呆滞范围
		$field_name = "nsale_days";
		if(is_numeric($filter['nsale_days_from'])){
			$nsale_days_from=intval($filter['nsale_days_from']);
		}
		if(is_numeric($filter['nsale_days_to'])){
			$nsale_days_to=intval($filter['nsale_days_to']);
		}
		if($nsale_days_from && $nsale_days_to){
// 			$filter["{$field_name}|between"][0] =  $nsale_days_from;
// 			$filter["{$field_name}|between"][1] =  $nsale_days_to;
			$filter["{$field_name}|bthan"] = $nsale_days_from;
			$filter["{$field_name}|sthan"] = $nsale_days_to;
		}elseif ($nsale_days_from){
			$filter["{$field_name}|bthan"] = $nsale_days_from;
		}elseif ($nsale_days_to){
			$filter["{$field_name}|sthan"] = $nsale_days_to;
		}
		unset($filter['nsale_days_from'],$filter['nsale_days_to']);
		
		return parent::_filter($filter,$tableAlias,$baseWhere);
	}
	
	function modifier_material_type($row){
		return $this->material_type_arr[$row];
	}
	
    /**
     * exportName
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function exportName(&$data){
		$data['name'] = $this->export_name.date("Y-m-d H:i:s");
	}
	
	//根据查询条件获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id){
	
		//根据选择的字段定义导出的第一行标题
		if($curr_sheet == 1){
			$data['content']['main'][] = $this->getExportTitle($fields);
		}
	
		if(!$productnsaledata = $this->getList('*', $filter, $start, $end)){
			return false;
		}
	
		foreach($productnsaledata as $var_data){
			// 获取科目
			$productnsaledataRow = array(
					"material_bn" => $var_data["material_bn"],
					"material_name" => $var_data["material_name"],
					"nsale_days" => $var_data["nsale_days"],
					"material_type" => $this->material_type_arr[$var_data["material_type"]],
					"barcode" => $var_data["barcode"],
					"create_time" => date('Y-m-d H:i:s',$var_data['create_time']),
					"balance_nums" => $var_data["balance_nums"],
					"now_num" => $var_data["now_num"],
					"inventory_cost" => $var_data["inventory_cost"],
					"unit_cost" => $var_data["unit_cost"],
					"now_unit_cost" => $var_data["now_unit_cost"],
					"now_inventory_cost" => $var_data["now_inventory_cost"],
			);
				
			$exptmp_data = array();
			foreach (explode(',', $fields) as $key => $col) {
				if(isset($productnsaledataRow[$col])){
					$productnsaledataRow[$col] = mb_convert_encoding($productnsaledataRow[$col], 'GBK', 'UTF-8');
					$exptmp_data[] = $productnsaledataRow[$col];
				}
                else
                {
                    $exptmp_data[]    = '';
                }
			}
			$data['content']['main'][] = implode(',', $exptmp_data);
				
		}
	
		return $data;
	}
	
}
