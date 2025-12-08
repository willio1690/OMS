<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_common_iostockrecord
{
	/*
	*获取出入库流水数据
	*/
	function get_iostock($branch_id=null,$start_time=null,$end_time=null,$bn=null,$offset=0,$limit=-1)
	{
		$iostock = app::get("ome")->model("iostock");
		$branch_product = app::get("ome")->model("branch_product");
		$stockcost_common_branchproduct = $this->get_instance_branchproduct();
		if(empty($bn)){//如果是查全部货品
			$product_data = $branch_product->getList("product_id",array("branch_id"=>$branch_id),$offset,$limit);
			if(!$product_data) return false;
		}
		else{
			$bn_arr = explode(',',$bn);
			$filter_product_id = $this->get_filter_product($bn_arr);
			$product_data = $branch_product->getList("product_id",array("branch_id"=>$branch_id,'product_id'=>$filter_product_id),$offset,$limit);
			if(!$product_data) return false;
		}
		foreach($product_data as $pk=>$pv)
		{
			$p_row = $this->product_sub($pv['product_id']);
			$ioData = $this->get_iostock_record($start_time,$end_time,$p_row['bn'],$branch_id);
			$startData = $stockcost_common_branchproduct->get_start($start_time,$pv['product_id'],$branch_id);
			if($startData){
				$start_data[0]['is_start'] = 1;
				$start_data[0]['stock_date'] = $startData['stock_date'];
				$start_data[0]['now_num'] = $startData['stock_num'];
				$start_data[0]['now_unit_cost'] = $startData['unit_cost'];
				$start_data[0]['now_inventory_cost'] = $startData['inventory_cost'];
				$ioData = array_merge($start_data,$ioData);
			}
			$p_row["iostock"] = $ioData;
			$aTmp[] = $p_row;
		}
		return $aTmp;
	}

	/*货品附属属性*/
	function product_sub($product_id)
	{
	    $basicMaterialLib    = kernel::single('material_basic_material');
	    
	    //基础物料信息
	    $p_row    = $basicMaterialLib->getBasicMaterialExt($product_id);
	    $p_row['product_id']    = $p_row['bm_id'];
	    $p_row['name']    = $p_row['material_name'];
	    $p_row['bn']    = $p_row['material_bn'];
	    
	    $type_row  = $brand_row = array();
	    
		$p_row['type_name'] = $type_row['name'];
		$p_row['brand_name'] = $brand_row['brand_name'];
		return $p_row;

	}

	/**
	 * 格式话货品ID搜索filter
	 */
	function get_filter_product($bn_arr)
	{
	    $basicMaterialObj = app::get('material')->model('basic_material');
	    
		$filter_product_id = array();
		
		$p_id_arr    = $basicMaterialObj->dump(array('material_bn'=>$bn_arr), 'bm_id');
		
		foreach($p_id_arr as $k=>$v)
		{
			$filter_product_id[] = $v['bm_id'];
		}
		return $filter_product_id;
	}
	/**
	 * 出入库标示
	 * @params $stock_type_id出入库类型ID
	 * @return array() 出入库类型数据
	 */
	function get_stock_type($stock_type_id)
	{
		$iostock_type = app::get("ome")->model("iostock_type");
		$aData = $iostock_type->getList("io_type,type_name",array("type_id"=>$stock_type_id));
		return $aData[0];
	}

	/**
	 * 出入库类型ID
	 * @params $io_type 出入库标示
	 * @return array() 出入库类型ID数组
	 */
	function get_type_id($io_type)
	{
		$iostock_type = app::get("ome")->model("iostock_type");
		$aData = $iostock_type->getList("type_id",array("io_type"=>$io_type));
		$type_id_data =array();
		foreach($aData as $k=>$val)
		{
			$type_id_data[] = $val['type_id'];
		}
		return $type_id_data;
	}

	/**
	 * 指定时间范围内的出入库流水数据
	 * @params $from_time 开始时间 2012-08-03 $to_time 结束时间 2012-08-04 $bn 货号,$branch_id 仓库ID
	 * @return array() 出入库流水记录数组
	 */
	function get_iostock_record($from_time,$to_time,$bn,$branch_id)
	{
		$from_time = strtotime($from_time);
		$stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
		if($from_time<$stockcost_install_time) $from_time = $stockcost_install_time;
		$to_time = strtotime($to_time)+(24*3600-1);
		$iostock = app::get("ome")->model("iostock");
		$ioData = $iostock->db->select("select i.*,t.type_name as io_type_name,t.io_type from sdb_ome_iostock as i left join sdb_ome_iostock_type as t on i.type_id=t.type_id where branch_id=".intval($branch_id)." and bn='".$bn."' and iotime>".intval($from_time)." and iotime<".intval($to_time)." order by iotime ASC");
		return $ioData;
	}

	function get_instance_branchproduct()
	{
		return kernel::single("tgstockcost_common_branchproduct");
	}

	/*导出链接URL
	*@params $params 链接参数
	*@return string();
	*/
	function get_export_href($params)
	{
		return '';
	}

    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @param mixed $pass_data 数据
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){
		return true;
	}
}