<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class iostock_crontab_script_analysisStockNsale{

    /**
     * analysisStockNsale
     * @return mixed 返回值
     */
    public function analysisStockNsale(){
        @set_time_limit(0);
        $db = kernel::database();
        
        //clear table
        $clear_sql = "truncate table ".kernel::database()->prefix."ome_analysis_stocknsale";
        $db->exec($clear_sql);
        
        $today_time = strtotime(date('Y-m-d',time())); //如1点调用 今天0点的时间戳
        $filter_create_time = $this->filter_create_time($today_time);
        $result_type_ids=array("3","5","7","10","40","100","300");
        
        $lastid = 0;
        $limit = 30;
        $product_stock_obj = app::get('material')->model('basic_material_stock');
        $product_obj = app::get('material')->model('basic_material');
        $reportStockNsaleObj = app::get('ome')->model('analysis_stocknsale');
        $basicMaterialCodeObj = app::get('material')->model('codebase');
        $branchObj = app::get('ome')->model('branch');
        do{
        	$product_info = $product_obj->getList('bm_id,material_bn',array('bm_id|than'=>$lastid),0,$limit," bm_id asc ");
        	if(!empty($product_info)){
	        	//update lastid
	        	$lastinfo = end($product_info);
	        	$lastid = $lastinfo["bm_id"];
	        	//useful want_product_info_arr
	        	$arr_product_ids = array();
	        	foreach ($product_info as $var_product_info){
	        		$arr_product_ids[] = $var_product_info["bm_id"];
	        	}
	        	$product_storethan0_bm_ids = $product_stock_obj->getList('bm_id',array('store|than'=>0,'bm_id|in'=>$arr_product_ids));
	        	if(empty($product_storethan0_bm_ids)){
	        		continue;
	        	}
	        	$arr_storethan0_product_bm_ids = array();
				foreach ($product_storethan0_bm_ids as $var_product_storethan0_bm_id){
					$arr_storethan0_product_bm_ids[] = $var_product_storethan0_bm_id["bm_id"];
	        	}
	        	$want_product_bns = array();
	        	foreach ($product_info as $iostock_product_info){
	        		if(!in_array($iostock_product_info["bm_id"],$arr_storethan0_product_bm_ids)){
	        			continue;
	        		}
	        		$sql_filter = array();
	        		$sql_filter[] = "create_time>=".$filter_create_time;
	        		$sql_filter[] = "create_time<".$today_time;
	        		$sql_filter[] = "type_id in(".implode(",",$result_type_ids).")";
	        		$sql_filter[] = "bn='".$iostock_product_info["material_bn"]."'";
	        		$sql_str = "select iostock_id from ".kernel::database()->prefix."ome_iostock 
	        					where ".implode(" AND ",$sql_filter)." limit 1";
	        		$result_rows = $db->select($sql_str);
					if(empty($result_rows)){
						$want_product_bns[] = $iostock_product_info['material_bn'];
					}
	        	}
	        	if(empty($want_product_bns)){
	        		continue;
	        	}
				$arr_fields_colums = array(
					'MAX(create_time) AS create_time',
					'bn as material_bn',
					'now_num',
					'inventory_cost',
					'now_inventory_cost',
					'unit_cost',
					'now_unit_cost',
					'balance_nums',
					'branch_id',
				);
				$sql_filter = array();
				$sql_filter[] = "bn in('".implode("','",$want_product_bns)."')";
				$sql_filter[] = "create_time<".$filter_create_time;
// 				$sql_filter[] = "type_id not in(".implode(",",$result_type_ids).")";
				//insert process begin
				$sql_str = "select ".implode(",",$arr_fields_colums)." from ".kernel::database()->prefix."ome_iostock 
							where ".implode(" AND ",$sql_filter)." group by bn,branch_id";
				$final_result_rows = $db->select($sql_str);
				if(empty($final_result_rows)){
					continue;
				}
				//get rl material_bn for material_name / branch_name / barcode
	        	$arr_material_bns = array();
				$arr_branch_ids = array();
				foreach ($final_result_rows as $var_final_result){
					if(!in_array($var_final_result["material_bn"],$arr_material_bns)){
						$arr_material_bns[] = $var_final_result["material_bn"];
					}
					if(!in_array($var_final_result["branch_id"],$arr_branch_ids)){
						$arr_branch_ids[] = $var_final_result["branch_id"];
					}
				}
				$material_rows = $product_obj->getList("bm_id,material_name,material_bn,type",array('material_bn|in'=>$arr_material_bns));
				$material_bm_ids = array();
				foreach ($material_rows as $var_material_row){
					$material_bm_ids[] = $var_material_row["bm_id"];
				}
				//for get bar code
				$material_barcode_rows = $basicMaterialCodeObj->getList("bm_id,code as barcode",array('bm_id|in'=>$material_bm_ids));
				$material_bm_id_for_barcode = array();
				foreach ($material_barcode_rows as $var_material_barcode_row){
					$material_bm_id_for_barcode[$var_material_barcode_row["bm_id"]] = $var_material_barcode_row["barcode"];
				}
				//for get branch_name
				$branch_rows = $branchObj->getList("branch_id,name as branch_name",array('branch_id|in'=>$arr_branch_ids));
				$branch_id_for_branch_name = array();
				foreach ($branch_rows as $var_branch_row){
					$branch_id_for_branch_name[$var_branch_row["branch_id"]]=$var_branch_row["branch_name"];
				}
				//get material_bn related to name/barcode/branch_name arr
				$material_bn_for_name_barcode = array();
				foreach ($material_rows as $result_material_row){
					$temp_arr_result=array();
					$temp_arr_result["material_name"] = $result_material_row["material_name"];
					$temp_arr_result["barcode"] = $material_bm_id_for_barcode[$result_material_row["bm_id"]];
					$temp_arr_result["material_type"] = $result_material_row["type"];
					$material_bn_for_name_barcode[$result_material_row["material_bn"]] = $temp_arr_result;
				}
				
				//for insert record
				foreach ($final_result_rows as $var_final_result){
					$insert_arr=$var_final_result;
                    
                    //material_bn
                    $material_bn = $var_final_result['material_bn'];
                    
                    //material_name
                    $material_name = $material_bn_for_name_barcode[$material_bn]['material_name'];
                    if(empty($material_name) && $material_bn){
                        $materialInfo = $product_obj->dump(array('material_bn'=>$material_bn), 'bm_id,material_name');
                        if($materialInfo){
                            $material_name = $materialInfo['material_name'];
                        }else{
                            //基础物料不存在,直接使用material_bn
                            $material_name = $material_bn;
                        }
                    }
                    
					$insert_arr["material_name"] = $material_name;
					$insert_arr["barcode"] = $material_bn_for_name_barcode[$var_final_result['material_bn']]['barcode'];
					$insert_arr["material_type"] = $material_bn_for_name_barcode[$var_final_result['material_bn']]['material_type'];
					$insert_arr["branch_name"] = $branch_id_for_branch_name[$var_final_result['branch_id']];
					//get nsale days
					$insert_arr['nsale_days'] = ceil(($today_time-intval($var_final_result["create_time"]))/86400);
	// 				$insert_arr['nsale_days'] = floor(($today_time-intval($var_final_result["create_time"]))/86400);
					$old = $reportStockNsaleObj->db_dump(['material_bn'=>$insert_arr['material_bn'], 'branch_id'=>$insert_arr['branch_id']], 'nsale_id');
					if($old) {
						$reportStockNsaleObj->update($insert_arr, ['nsale_id'=>$old['nsale_id']]);
					} else {
						$reportStockNsaleObj->insert($insert_arr);
					}
				}
        	}	
        }while(!empty($product_info));
    }
    
    private function filter_create_time($today_time){
    	//get and default days limit
    	$iostock_app=app::get("iostock");
    	if(!$iostock_app->getConf("report_stock_nsale_days")){
    		$iostock_app->setConf("report_stock_nsale_days", 90);//default 90 days
    	}
    	$nsale_days = $iostock_app->getConf("report_stock_nsale_days");
    	$int_nsale_days = 86400*$nsale_days;
    	$filter_create_time = $today_time-$int_nsale_days;
    	return $filter_create_time;
    }

}
?>