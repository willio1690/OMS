<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_salesmaterial{

    protected $_type = array(
        '1' => '普通',
        '2' => '组合',
        '3' => '赠品',
        '4' => '福袋',
        '5' => '多选一',
    );

    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($filter,$offset=0,$limit=40)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $basicMaterialStockObj   = app::get('material')->model('basic_material_stock');
        $salesMaterialObj   = app::get('material')->model('sales_material');
        $salesMaterialExtObj   = app::get('material')->model('sales_material_ext');
        $lib_ma_ba_ma = kernel::single('material_basic_material');
        $mdl_ma_go_ty = app::get('ome')->model('goods_type');

        $goodscount = $salesMaterialObj->count($filter);
        $goodslist = $salesMaterialObj->getList('*',$filter,$offset,$limit);
        if(empty($goodslist)){
            return array('list' => array(),'count' => $goodscount);
        }
        $smIds = [];
        $luckybag_smIds = [];
        $pickone_smIds = [];
        foreach ($goodslist as $value) {
            $smIds[] = $value['sm_id'];
            if($value['sales_material_type'] == 4){
                $luckybag_smIds[] = $value['sm_id'];
            }elseif($value['sales_material_type'] == 5){
                $pickone_smIds[] = $value['sm_id'];
            }
        }
        $salesMaterialExtListRaw = $salesMaterialExtObj->getList('sm_id,unit,retail_price', array('sm_id'=>$smIds));
        $salesMaterialExtList = array();
        foreach ($salesMaterialExtListRaw as $ext) {
            $salesMaterialExtList[$ext['sm_id']] = $ext;
        }
        $bmIds = [];
        $arrLuckybag = [];
        if(!empty($luckybag_smIds)){
            $mdl_ma_sa_ma = app::get('material')->model('luckybag_rules');
            $rs_luckybag_info = $mdl_ma_sa_ma->getList("*",array("sm_id"=>$luckybag_smIds));
            foreach ($rs_luckybag_info as $value) {
                $arrLuckybag[$value['sm_id']][] = $value;
                $bmIds = array_merge($bmIds, explode(",", $value['bm_ids']));
            }
            unset($rs_luckybag_info);
        }
        $arrPickone = [];
        if(!empty($pickone_smIds)){
            $mdl_ma_sa_ma = app::get('material')->model('pickone_rules');
            $rs_pickone_info = $mdl_ma_sa_ma->getList("*",array("sm_id"=>$pickone_smIds));
            foreach ($rs_pickone_info as $value) {
                $arrPickone[$value['sm_id']][] = $value;
                $bmIds[] = $value['bm_id'];
            }
            unset($rs_pickone_info);
        }
        $smbm = app::get('material')->model('sales_basic_material')->getList('*', ['sm_id'=>$smIds]);
        $arrSmBm = [];
        foreach ($smbm as $value) {
            $bmIds[] = $value['bm_id'];
            $arrSmBm[$value['sm_id']][] = $value;
        }
        unset($smbm);
        $bmIds = array_unique($bmIds);
        $bmInfoList = [];
        $arrStoreInfo = [];
        $arrType = [];
        if(!empty($bmIds)){
            //a.bm_id, a.material_bn, a.material_name, a.visibled, b.cost, b.retail_price, b.weight, b.unit, b.specifications, b.brand_id, b.cat_id, c.code
            $bmInfoList = $lib_ma_ba_ma->getBasicMaterialByBmids($bmIds);
            $bmInfoList = array_column($bmInfoList, null, 'bm_id');
            $arrStoreInfo = $basicMaterialStockObj->getList('bm_id,store,store_freeze', array('bm_id'=>$bmIds));
            $arrStoreInfo = array_column($arrStoreInfo, null, 'bm_id');
            $arrType = $mdl_ma_go_ty->getList('type_id,name', ['type_id'=>array_column($bmInfoList, 'cat_id')]);
            $arrType = array_column($arrType, null, 'type_id');
        }
        $list = array();
        $formatFilter=kernel::single('openapi_format_abstract');
        foreach ((array) $goodslist as $value) {
            $unit = isset($salesMaterialExtList[$value['sm_id']]['unit']) ? $salesMaterialExtList[$value['sm_id']]['unit'] : '';
            $retail_price = isset($salesMaterialExtList[$value['sm_id']]['retail_price']) ? $salesMaterialExtList[$value['sm_id']]['retail_price'] : '';

            $good = array(
                'sales_material_bn'    => $formatFilter->charFilter($value['sales_material_bn']),
                'sales_material_name'  => $formatFilter->charFilter($value['sales_material_name']),
                'unit'        => $formatFilter->charFilter($unit),
                'sales_material_type'       => $value['sales_material_type'],
                'sales_material_type_name' => $salesMaterialObj->modifier_sales_material_type($value['sales_material_type']),
                'retail_price'       => $retail_price,
                'shop_id'  => $value['shop_id'],
            );
            
            if($value['sales_material_type'] == 4){ //福袋
                $rs_luckybag_info = isset($arrLuckybag[$value['sm_id']]) ? $arrLuckybag[$value['sm_id']] : [];
                foreach($rs_luckybag_info as $var_l_i){
                    $basic_materials = array();
                    $bm_ids_arr = explode(",", $var_l_i["bm_ids"]);
                    foreach($bm_ids_arr as $var_bm_id){
                        $rs_basic_ma_info = isset($bmInfoList[$var_bm_id]) ? $bmInfoList[$var_bm_id] : [];
                        $storeInfo = isset($arrStoreInfo[$var_bm_id]) ? $arrStoreInfo[$var_bm_id] : [];
                        $basic_materials[] = array(
                            "material_bn" => $formatFilter->charFilter($rs_basic_ma_info['material_bn']),
                            "material_name" => $formatFilter->charFilter($rs_basic_ma_info['material_name']),
                            "store" => $storeInfo['store'],
                            "store_freeze" => $storeInfo['store_freeze'],
                            "weight" => $rs_basic_ma_info['weight'],
                            "cost" => $rs_basic_ma_info['cost'],
                            "retail_price" => $rs_basic_ma_info['retail_price'],
                            "barcode" => $rs_basic_ma_info["code"],
                            'number' => 1, //关联的基础物料数量
                            'rate' => 100, //关联的基础物料贡献比
                        );
                    }
                    $luckybagInfo = array(
                        "lbr_id" => $var_l_i["lbr_id"],
                        "lbr_name" => $var_l_i["lbr_name"],
                        "sku_num" => $var_l_i["sku_num"],
                        "send_num" => $var_l_i["send_num"],
                        "price" => $var_l_i["price"],
                        "basic_materials" => $basic_materials,
                    );
                    $luckybagInfo = array_map(function($info) {return is_null($info) ? '' : $info;}, $luckybagInfo);
                    $good['luckybag_rules'][] = $luckybagInfo;
                }
            }elseif($value['sales_material_type'] == 5){ //多选一
                $rs_pickone_info = isset($arrPickone[$value['sm_id']]) ? $arrPickone[$value['sm_id']] : [];
                $good["pickone_select_type"] = ($rs_pickone_info[0]["select"] == "2") ? "排序" : "随机";
                foreach($rs_pickone_info as $var_p_i){
                    $rs_basic_ma_info = isset($bmInfoList[$var_p_i["bm_id"]]) ? $bmInfoList[$var_p_i["bm_id"]] : [];
                    $storeInfo = isset($arrStoreInfo[$var_p_i["bm_id"]]) ? $arrStoreInfo[$var_p_i["bm_id"]] : [];
                    $pickoneInfo = array(
                        "sort" => $var_p_i["sort"],
                        "material_bn" => $formatFilter->charFilter($rs_basic_ma_info['material_bn']),
                        "material_name" => $formatFilter->charFilter($rs_basic_ma_info['material_name']),
                        "store" => $storeInfo['store'],
                        "store_freeze" => $storeInfo['store_freeze'],
                        "weight" => $rs_basic_ma_info['weight'],
                        "cost" => $rs_basic_ma_info['cost'],
                        "retail_price" => $rs_basic_ma_info['retail_price'],
                        "barcode" => $rs_basic_ma_info["code"],
                        'number' => 1, //关联的基础物料数量
                        'rate' => 100, //关联的基础物料贡献比
                    );
                    $pickoneInfo = array_map(function($info) {return is_null($info) ? '' : $info;}, $pickoneInfo);
                    $good['pickone_rules'][] = $pickoneInfo;
                }
            }else{
                if(isset($arrSmBm[$value['sm_id']])){
                    foreach($arrSmBm[$value['sm_id']] as $basicMInfo){
                        $rs_basic_ma_info = isset($bmInfoList[$basicMInfo['bm_id']]) ? $bmInfoList[$basicMInfo['bm_id']] : [];
                        $storeInfo = isset($arrStoreInfo[$basicMInfo['bm_id']]) ? $arrStoreInfo[$basicMInfo['bm_id']] : [];
                        $typeRow = isset($arrType[$rs_basic_ma_info['cat_id']]) ? $arrType[$rs_basic_ma_info['cat_id']] : [];
                        
                        $basicInfo = array(
                                'material_bn'   => $formatFilter->charFilter($rs_basic_ma_info['material_bn']),
                                'material_name'   => $formatFilter->charFilter($rs_basic_ma_info['material_name']),
                                'store'        => $storeInfo['store'],
                                'store_freeze' => $storeInfo['store_freeze'],
                                'weight'       => $rs_basic_ma_info['weight'],
                                'cost'         => $rs_basic_ma_info['cost'],
                                'goods_type_id'   => $typeRow['type_id'],
                                'goods_type_name' => $typeRow['name'],
                                'retail_price' => $rs_basic_ma_info['retail_price'],
                                'barcode'      => $formatFilter->charFilter($rs_basic_ma_info['code']),
                                'number' => $basicMInfo['number'], //关联的基础物料数量
                                'rate' => $basicMInfo['rate'], //关联的基础物料贡献比
                        );
                        $basicInfo = array_map(function($info) {return is_null($info) ? '' : $info;}, $basicInfo);
                        $good['basic_materials'][] = $basicInfo;
                    }
                }
            }
            $good = array_map(function($v) {return is_null($v) ? '' : $v;}, $good);
            
            $list[] = $good;
        }

        return array('list' => $list,'count' => (int) $goodscount);
    }

    /**
     * 销售物料新增检查
     * @param Array $params
     * @param String $err_msg
     * @return Array
     */
    private function _checkAddParams(&$params, &$err_msg){
        if(empty($params['sales_material_name']) || empty($params['sales_material_bn'])){
            $err_msg ="必填信息不能为空";
            return false;
        }
        
        //去除空格
        $params['sales_material_bn'] = trim($params['sales_material_bn']);
        
        //销售物料信息
        $basicMaterialObj = app::get('material')->model('basic_material');
        $salesMaterialObj = app::get('material')->model('sales_material');
        $salesMaterialInfo = $salesMaterialObj->getList('sales_material_bn',array('sales_material_bn'=>$params['sales_material_bn']));
        if($salesMaterialInfo){
            $err_msg ="当前新增的物料编码已被使用，不能重复";
            return false;
        }

        $params['sales_material_bn_crc32'] = sprintf('%u',crc32($params['sales_material_bn']));

        if($params['sales_material_type'] == 4){ //福袋
            $result_check_format = $this->check_luckybag_content($params,$err_msg);
            if(!$result_check_format && $err_msg){
                return false;
            }
            $params["luckybag_arr"] = $result_check_format;
        }elseif($params['sales_material_type'] == 5){ //多选一
            $result_check_format = $this->check_pickone_content($params,$err_msg);
            if(!$result_check_format && $err_msg){
                return false;
            }
            $params["pickone_arr"] = $result_check_format;
        }else{ //原有走的促销 普通 赠品
            if($params['sales_material_type'] == 2){
                if(!isset($params['at'])){
                    $err_msg ="组合物料请至少设置一个物料明细内容";
                    return false;
                }
                
                $basicM_bns = $tmp_at = $tmp_pr = array();
                foreach ($params['at'] as $bn => $val){
                    if (count($params['at']) == 1){
                        if ($val <2){
                            $err_msg ="只有一种物料时，数量必须大于1";
                            return false;
                        }
                    }else {
                        if ($val < 1){
                            $err_msg ="数量必须大于0";
                            return false;
                        }
                    }
                    $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$bn), 0, 1);
                    if(!$basicInfo){
                        $err_msg ="找不到关联的基础物料";
                        return false;
                    }else{
                        $tmp_at[$basicInfo[0]['bm_id']] = $val;
                        $basicM_bns[$bn] = $basicInfo[0]['bm_id'];
                    }
                }
                unset($params['at']);
                $params['at'] = $tmp_at;
                
                foreach ($params['pr'] as $bn => $val){
                    $tmp_rate +=$val;
                    $tmp_pr[$basicM_bns[$bn]] = $val;
                }
                unset($params['pr']);
                $params['pr'] = $tmp_pr;
                
                if($tmp_rate > 100){
                    $err_msg ="分摊销售价合计百分比:".$tmp_rate.",已超100%";
                    return false;
                }elseif($tmp_rate < 100){
                    $err_msg ="分摊销售价合计百分比:".$tmp_rate.",不足100%";
                    return false;
                }
            }else{
                if(isset($params['bind_bn'])){
                    $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$params['bind_bn']), 0, 1);
                    if(!$basicInfo){
                        $err_msg ="找不到关联的基础物料";
                        return false;
                    }else{
                        $params['bind_bm_id'] = $basicInfo[0]['bm_id'];
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * 添加
     * @param mixed $data 数据
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($data,&$code,&$sub_msg){
        $result = array('rsp'=>'succ');

        if(!$this->_checkAddParams($data, $error_msg)){
            $result['rsp'] = 'fail';
            $result['msg'] = $error_msg;
            return $result;
        }

        $salesMaterialObj = app::get('material')->model('sales_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $salesMaterialShopFreezeObj = app::get('material')->model('sales_material_shop_freeze');
        $formatFilter=kernel::single('openapi_format_abstract');

        $shop_id = $data['shop_id'] ? $data['shop_id'] : '_ALL_';
        //保存物料主表信息
        $addData = array(
            'sales_material_name' => $formatFilter->charFilter($data['sales_material_name']),
            'sales_material_bn' => $formatFilter->charFilter($data['sales_material_bn']),
            'sales_material_bn_crc32' => $data['sales_material_bn_crc32'],
            'sales_material_type' => $data['sales_material_type'],
            'shop_id' => $shop_id,
            'create_time' => time(),
            'tax_code'          =>  $data['tax_code'],
            'tax_name'          =>  $data['tax_name'],
            'tax_rate'          =>  $data['tax_rate'],
        );
        $is_save = $salesMaterialObj->save($addData);
        if($is_save){
            $is_bind = false;
            //如果有关联物料就做绑定操作
            $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
            //普通销售物料关联
            if(($data['sales_material_type'] == 1 || $data['sales_material_type'] == 3 || $data['sales_material_type'] == 6) && !empty($data['bind_bm_id'])){
                $addBindData = array(
                        'sm_id' => $addData['sm_id'],
                        'bm_id' => $data['bind_bm_id'],
                        'number' => 1,
                );
                $salesBasicMaterialObj->insert($addBindData);
                $is_bind = true;
            }elseif($data['sales_material_type'] == 2 && !empty($data['at'])){
            //促销销售物料关联
                foreach($data['at'] as $k=>$v){
                    $addBindData = array(
                        'sm_id' => $addData['sm_id'],
                        'bm_id' => $k,
                        'number' => $v,
                        'rate' => $data['pr'][$k],
                    );
                    $salesBasicMaterialObj->insert($addBindData);
                    $addBindData = null;
                }
                $is_bind = true;
            }elseif($data['sales_material_type'] == 4 && !empty($data['luckybag_arr'])){ //福袋
                $mdl_material_luckybag_rules = app::get('material')->model('luckybag_rules');
                foreach($data['luckybag_arr'] as $var_lbr){
                    $addBindData = array(
                            "lbr_name" => $var_lbr["lbr_name"],
                            "sm_id" => $addData['sm_id'],
                            "bm_ids" => implode(",", $var_lbr["bm_ids"]),
                            "sku_num" => $var_lbr["sku_num"],
                            "send_num" => $var_lbr["send_num"],
                            "price" => $var_lbr["price"],
                    );
                    $mdl_material_luckybag_rules->insert($addBindData);
                }
                $is_bind = true;
            }elseif($data['sales_material_type'] == 5 && !empty($data['pickone_arr'])){ //多选一
                $mdl_material_pickone_rules = app::get('material')->model('pickone_rules');
                foreach($data['pickone_arr'] as $var_dpa){
                    $addBindData = array(
                            "sm_id" => $addData['sm_id'],
                            "bm_id" => $var_dpa["bm_id"],
                            "sort" => $var_dpa["sort"],
                            "select_type" => $var_dpa["select_type"],
                    );
                    $mdl_material_pickone_rules->insert($addBindData);
                }
                $is_bind = true;
            }

            //如果有绑定物料数据，设定销售物料为绑定状态
            if($is_bind){
                $salesMaterialObj->update(array('is_bind'=>1),array('sm_id'=>$addData['sm_id']));
            }

            //保存销售物料扩展信息
            $addExtData = array(
                'sm_id' => $addData['sm_id'],
                'cost' => $data['cost'] ? $data['cost'] : 0.00,
                'retail_price' => $data['retail_price'] ? $data['retail_price'] : 0.00,
                'weight' => $data['weight'] ? $data['weight'] : 0.00,
                'unit' => $data['unit'],
            );
            $salesMaterialExtObj->insert($addExtData);
            
            //保存销售物料店铺级冻结
            if($shop_id != '_ALL_'){
                $addStockData = array(
                    'sm_id' => $addData['sm_id'],
                    'shop_id' => $shop_id,
                    'shop_freeze' => 0,
                );
                $salesMaterialShopFreezeObj->insert($addStockData);
            }
        }else{
            $result = array('msg'=>'销售物料添加失败', 'rsp'=>'fail');
        }

        return $result;
    }


    /**
     * 销售物料编辑时的参数检查方法
     * 
     * @param Array $params 
     * @param String $err_msg
     * @return Boolean
     */
    function checkEditParams(&$params, &$err_msg){

        if(empty($params['sales_material_name']) || empty($params['sales_material_bn'])){
            $err_msg ="必填信息不能为空";
            return false;
        }
        
        //去除空格
        $params['sales_material_bn'] = trim($params['sales_material_bn']);
        
        //销售物料信息
        $basicMaterialObj = app::get('material')->model('basic_material');
        $salesMaterialObj = app::get('material')->model('sales_material');
        $salesMaterialExistInfo = $salesMaterialObj->getList('*',array('sales_material_bn'=>$params['sales_material_bn']));
        if(!$salesMaterialExistInfo){
            $err_msg ="当前物料不存在";
            return false;
        }else{
            $params['sm_id'] = $salesMaterialExistInfo[0]['sm_id'];
            $params['old_sm_info'] = $salesMaterialExistInfo[0];
        }
        
        if($params['sales_material_type'] == 4){ //福袋
            $result_check_format = $this->check_luckybag_content($params,$err_msg,true);
            if(!$result_check_format && $err_msg){
                return false;
            }
            $params["luckybag_arr"] = $result_check_format;
        }elseif($params['sales_material_type'] == 5){ //多选一
            $result_check_format = $this->check_pickone_content($params,$err_msg);
            if(!$result_check_format && $err_msg){
                return false;
            }
            $params["pickone_arr"] = $result_check_format;
        }else{
            $basicM_bns = $tmp_at = $tmp_pr = array();
            if($params['sales_material_type'] == 2){
                if(!isset($params['at'])){
                    $err_msg ="组合物料请至少设置一个物料明细内容";
                    return false;
                }
                
                foreach ($params['at'] as $bn => $val){
                    if (count($params['at']) == 1){
                        if ($val <2){
                            $err_msg ="只有一种物料时，数量必须大于1";
                            return false;
                        }
                    }else {
                        if ($val < 1){
                            $err_msg ="数量必须大于0";
                            return false;
                        }
                    }
                    $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$bn), 0, 1);
                    if(!$basicInfo){
                        $err_msg ="找不到关联的基础物料";
                        return false;
                    }else{
                        $tmp_at[$basicInfo[0]['bm_id']] = $val;
                        $basicM_bns[$bn] = $basicInfo[0]['bm_id'];
                    }
                }
                unset($params['at']);
                $params['at'] = $tmp_at;
                
                foreach ($params['pr'] as $bn => $val){
                    $tmp_rate +=$val;
                    $tmp_pr[$basicM_bns[$bn]] = $val;
                }
                unset($params['pr']);
                $params['pr'] = $tmp_pr;
                
                if($tmp_rate > 100){
                    $err_msg ="分摊销售价合计百分比:".$tmp_rate.",已超100%";
                    return false;
                }elseif($tmp_rate < 100){
                    $err_msg ="分摊销售价合计百分比:".$tmp_rate.",不足100%";
                    return false;
                }
            }else{
                if(isset($params['bind_bn'])){
                    $basicInfo = $basicMaterialObj->getList('bm_id', array('material_bn'=>$params['bind_bn']), 0, 1);
                    if(!$basicInfo){
                        $err_msg ="找不到关联的基础物料";
                        return false;
                    }else{
                        $params['bind_bm_id'] = $basicInfo[0]['bm_id'];
                    }
                }
            }
        }

        return true;
    }

    /**
     * 检查基础物料个别参数是否可编辑
     * 
     * @param Int $bm_id
     * @return Array
     */
    function checkEditReadOnly($shop_id, $sm_id, $is_bind){
        $readonly = array('type' => false,'shop'=>false,'bind_item'=>false);

        //如果销售物料有冻结、订单，那么物料类型不能变
        if($shop_id != '_ALL_'){
            $shopFreezeObj = app::get('material')->model('sales_material_shop_freeze');
            $storeInfo = $shopFreezeObj->getList('shop_freeze',array('sm_id'=>$sm_id,'shop_id'=>$shop_id));
            if($storeInfo[0]['shop_freeze'] > 0){
                $is_type_readonly = true;
            }
        }

        $orderObjObj = app::get('ome')->model('order_objects');
        $orderInfo = $orderObjObj->getList('obj_id,goods_id',array('goods_id'=>$sm_id));
        if($orderInfo){
            $has_object = true;
            $is_type_readonly = true;
            $is_shop_readonly = true;
        }

        if($is_type_readonly){
            $readonly['type'] = true;
        }

        if($is_shop_readonly){
            $readonly['shop'] = true;
        }

        //已绑定有订单的不能变
        if($is_bind == 1 && $has_object == true){
            $orderItemObj = app::get('ome')->model('order_items');
            $orderItemInfo = $orderItemObj->db->select("select item_id from sdb_ome_order_items as oi left join sdb_ome_order_objects as oo on oi.obj_id = oo.obj_id where goods_id = ".$sm_id." LIMIT 0,1");
            if($orderItemInfo){
                $is_bind_item_readonly = true;
            }
        }

        if($is_bind_item_readonly){
            $readonly['bind_item'] = true;
        }

        return $readonly;
    }

    /**
     * edit
     * @param mixed $data 数据
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function edit($data,&$code,&$sub_msg){
        $result = array('rsp'=>'succ');

        if(!$this->checkEditParams($data, $error_msg)){
            $result['rsp'] = 'fail';
            $result['msg'] = $error_msg;
            return $result;
        }

        $salesMaterialObj = app::get('material')->model('sales_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');

        //检查物料是否有关联的订单,关联的基础物料不能改变
        $readonly = $this->checkEditReadOnly($data['old_sm_info']['shop_id'], $data['sm_id'], $data['old_sm_info']['is_bind']);
        $data['sales_material_type'] = $readonly['type'] ? $data['old_sm_info']['sales_material_type'] : $data['sales_material_type'];
        $data['shop_id'] = $readonly['shop'] ? $data['old_sm_info']['shop_id'] : '_ALL_';

        //更新基础物料基本信息
        $updateData['sales_material_name'] = $data['sales_material_name'];
        $updateData['sales_material_type'] = $data['sales_material_type'];
        $updateData['shop_id'] = $data['shop_id'];

        if($data['tax_code']){
            $updateData['tax_code'] = $data['tax_code'];
        }
        if($data['tax_name']){
            $updateData['tax_name'] = $data['tax_name'];
        }
        if($data['tax_rate']){
            $updateData['tax_rate'] = $data['tax_rate'];
        }
        $filter['sm_id'] = $data['sm_id'];

        $is_update = $salesMaterialObj->update($updateData,$filter);
        if($is_update){
            $is_bind = false;
            //如果有关联物料就做绑定操作
            $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
            
            //如果销售物料有对应订单，则不允许删除关联的基础物料信息
            if(!$readonly['bind_item']){
                //删除原有关联基础物料信息
                $salesBasicMaterialObj->delete(array('sm_id'=>$filter['sm_id']));
                //多选一 先删除原有的 加新的
                $mdl_material_pickone_rules = app::get('material')->model('pickone_rules');
                $rs_pickone_old = $mdl_material_pickone_rules->dump(array("sm_id"=>$filter['sm_id']));
                if(!empty($rs_pickone_old)){
                    $mdl_material_pickone_rules->delete(array("sm_id" => $filter['sm_id']));
                }
            }
            
            
            //目前如果编辑时sales_material_type不是4福袋 原销售物料时福袋 删除原有的福袋数据
            $mdl_material_luckybag_rules = app::get('material')->model('luckybag_rules');
            if($data['sales_material_type'] != 4){
                $rs_old_luckybag = $mdl_material_luckybag_rules->dump(array("sm_id"=>$filter['sm_id']));
                if(!empty($rs_old_luckybag)){ //有旧的福袋数据删除sm_id对应的所有lbr数据
                    $mdl_material_luckybag_rules->delete(array("sm_id"=>$filter['sm_id']));
                }
            }

            //普通销售物料关联
            if(($data['sales_material_type'] == 1 || $data['sales_material_type'] == 3 || $data['sales_material_type'] == 6) && (!$readonly['bind_item']) && !empty($data['bind_bm_id'])){
                $addBindData = array(
                        'sm_id' => $filter['sm_id'],
                        'bm_id' => $data['bind_bm_id'],
                        'number' => 1,
                );
                $salesBasicMaterialObj->insert($addBindData);

                $is_bind = true;
            }elseif($data['sales_material_type'] == 2 && (!$readonly['bind_item']) && !empty($data['at'])){
            //促销销售物料关联
                foreach($data['at'] as $k=>$v){
                    $addBindData = array(
                        'sm_id' => $filter['sm_id'],
                        'bm_id' => $k,
                        'number' => $v,
                        'rate' => $data['pr'][$k],
                    );
                    $salesBasicMaterialObj->insert($addBindData);
                    $addBindData = null;
                }

                $is_bind = true;
            }elseif($data['sales_material_type'] == 4 && !$readonly['bind_item'] && !empty($data['luckybag_arr'])){ //福袋
                //这里根据sm_id和lbr_name判断是否存在此组合 存在更新 不存在新增 多余的删除
                $rs_luckybag_list = $mdl_material_luckybag_rules->getList("lbr_id,lbr_name",array("sm_id"=>$filter['sm_id']));
                if(empty($rs_luckybag_list)){ //修改sales_material_type为4过来的 做新增
                    foreach($data['luckybag_arr'] as $var_lbr){
                        $addBindData = array(
                                "lbr_name" => $var_lbr["lbr_name"],
                                "sm_id" => $filter['sm_id'],
                                "bm_ids" => implode(",", $var_lbr["bm_ids"]),
                                "sku_num" => $var_lbr["sku_num"],
                                "send_num" => $var_lbr["send_num"],
                                "price" => $var_lbr["price"],
                        );
                        $mdl_material_luckybag_rules->insert($addBindData);
                    }
                }else{ //做更新
                    $lbr_name_arr = array();
                    foreach($data['luckybag_arr'] as $var_lbr){
                        $lbr_name_arr[] = $var_lbr["lbr_name"];
                    }
                    $del_lbr_id_arr = array();
                    foreach($rs_luckybag_list as $var_rll){
                        if(!in_array($var_rll["lbr_name"],$lbr_name_arr)){
                            $del_lbr_id_arr[] = $var_rll["lbr_id"];
                        }
                    }
                    if(!empty($del_lbr_id_arr)){
                        $mdl_material_luckybag_rules->delete(array("lbr_id"=>$del_lbr_id_arr));
                    }
                    foreach($data['luckybag_arr'] as $var_lbr_v2){
                        $filter_arr = array("lbr_name"=>$var_lbr_v2["lbr_name"],"sm_id"=>$filter['sm_id']);
                        $rs_lkb_info = $mdl_material_luckybag_rules->dump($filter_arr,"lbr_id");
                        if(empty($rs_lkb_info)){ //新增的
                            $addBindData = array(
                                    "lbr_name" => $var_lbr_v2["lbr_name"],
                                    "sm_id" => $filter['sm_id'],
                                    "bm_ids" => implode(",", $var_lbr_v2["bm_ids"]),
                                    "sku_num" => $var_lbr_v2["sku_num"],
                                    "send_num" => $var_lbr_v2["send_num"],
                                    "price" => $var_lbr_v2["price"],
                            );
                            $mdl_material_luckybag_rules->insert($addBindData);
                        }else{
                            $update_arr = array(
                                "bm_ids" => implode(",", $var_lbr_v2["bm_ids"]),
                                "sku_num" => $var_lbr_v2["sku_num"],
                                "send_num" => $var_lbr_v2["send_num"],
                                "price" => $var_lbr_v2["price"],
                            );
                            $mdl_material_luckybag_rules->update($update_arr,$filter_arr);
                        }
                    }
                }
                $is_bind = true;
            }elseif($data['sales_material_type'] == 5 && !$readonly['bind_item'] && !empty($data['pickone_arr'])){ //多选一
                $mdl_material_pickone_rules = app::get('material')->model('pickone_rules');
                foreach($data['pickone_arr'] as $var_dpa){
                    $addBindData = array(
                        "sm_id" => $filter['sm_id'],
                        "bm_id" => $var_dpa["bm_id"],
                        "sort" => $var_dpa["sort"],
                        "select_type" => $var_dpa["select_type"],
                    );
                    $mdl_material_pickone_rules->insert($addBindData);
                }
                $is_bind = true;
            }
            
            if($readonly['bind_item']){ //如果该销售物料有订单的
                $is_bind = true;
            }

            //如果有绑定物料数据或者有订单的，设定销售物料为绑定状态
            if($is_bind){
                $salesMaterialObj->update(array('is_bind'=>1),array('sm_id'=>$filter['sm_id']));
            }else{
                $salesMaterialObj->update(array('is_bind'=>2),array('sm_id'=>$filter['sm_id']));
            }

            //更新销售物料扩展信息
            $updateExtData = array(
                'cost' => $data['cost'] ? $data['cost'] : 0.00,
                'retail_price' => $data['retail_price'] ? $data['retail_price'] : 0.00,
                'weight' => $data['weight'] ? $data['weight'] : 0.00,
                'unit' => $data['unit'],
            );
            $salesMaterialExtObj->update($updateExtData, $filter);
        }else{
            $result = array('msg'=>'销售物料更新失败', 'rsp'=>'fail');
        }

        return $result;
    }
    
    //检查填写的福袋组合信息格式内容
    private function check_luckybag_content($params,&$err_msg,$edit=false){
        if(!is_numeric($params["retail_price"]) || $params["retail_price"]< 0){
            $err_msg = "销售价必须是大于等于0的数字";return false;
        }
        $luckybag_bind_info = trim($params["luckybag_bind_info"]);
        if(!$luckybag_bind_info){
            $err_msg = "福袋类关联物料信息不能为空";return false;
        }
        $bm_ids_record = array();
        $return_luckybag_rules = array();
        $arr_lbr_name = array();
        $reg_number = "/^[1-9][0-9]*$/";
        $luckybag_total_price = 0;
        $basicMaterialObj = app::get('material')->model('basic_material');
        $tmp_luckybag_rules = explode('#', $luckybag_bind_info);
        foreach($tmp_luckybag_rules as $var_tlr){
            $current_bm_ids = array();
            $tmp_luckybag_name = explode(':', $var_tlr);
            if(count($tmp_luckybag_name) == 2){
                if(!$tmp_luckybag_name[0]){
                    $err_msg = "福袋组合名称异常";break;
                }
                if(!$tmp_luckybag_name[1]){
                    $err_msg = "福袋组合内容异常";break;
                }
                if(in_array($tmp_luckybag_name[0],$arr_lbr_name)){
                    $err_msg = "组合名称不能相同";break;
                }else{
                    $arr_lbr_name[] = $tmp_luckybag_name[0];
                }
                $tmp_luckybag_detail = explode('-', $tmp_luckybag_name[1]);
                if(count($tmp_luckybag_detail) == 2){
                    if(!$tmp_luckybag_detail[0]){
                        $err_msg = $tmp_luckybag_name[0]."：的福袋组合基础物料异常";break;
                    }
                    if(!$tmp_luckybag_detail[1]){
                        $err_msg = $tmp_luckybag_name[0]."：的福袋组合规则参数异常";break;
                    }
                    $luckybag_bm_bns = explode("|", $tmp_luckybag_detail[0]);
                    $rs_bm_ids = $basicMaterialObj->getList("bm_id",array("material_bn"=>$luckybag_bm_bns));
                    if(empty($rs_bm_ids)){
                        $err_msg = $tmp_luckybag_name[0]."：的福袋组合基础物料不存在";break;
                    }
                    if(count($rs_bm_ids) < count($luckybag_bm_bns)){
                        $err_msg = $tmp_luckybag_name[0]."：的福袋组合基础物料编码重复或不存在";break;
                    }
                    $luckybag_params = explode("|", $tmp_luckybag_detail[1]);
                    if(count($luckybag_params) != 3){
                        $err_msg = $tmp_luckybag_name[0]."：的福袋组合参数异常";break;
                    }
                    $duplicate_bm_id_flag = false;
                    foreach($rs_bm_ids as $var_bi){
                        if(in_array($var_bi["bm_id"],$bm_ids_record)){
                            $duplicate_bm_id_flag = true;break;
                        }else{
                            $current_bm_ids[] = $var_bi["bm_id"]; //当前组合
                            $bm_ids_record[] = $var_bi["bm_id"]; //所有组合
                        }
                    }
                    if($duplicate_bm_id_flag){
                        $err_msg = "所有组合中基础物料不得重复";break;
                    }
                    if(!preg_match($reg_number,$luckybag_params[0])){
                        $err_msg = $tmp_luckybag_name[0]."：sku数必须是大于0的整数";break;
                    }
                    if(!preg_match($reg_number,$luckybag_params[1])){
                        $err_msg = $tmp_luckybag_name[0]."：送件数必须是大于0的整数";break;
                    }
                    if($luckybag_params[0] > count($current_bm_ids)){
                        $err_msg = $tmp_luckybag_name[0]."：sku数不得大于当前基础物料sku数量";break;
                    }
                    if(!is_numeric($luckybag_params[2]) || $luckybag_params[2] < 0){
                        $err_msg = $tmp_luckybag_name[0]."：单品售价必须是大于等于0的数字";break;
                    }
                    $luckybag_total_price = $luckybag_total_price + ($luckybag_params[0]*$luckybag_params[1]*$luckybag_params[2]);
                    //当前组合检查完成赋值
                    $return_luckybag_rules[] = array(
                        "lbr_name" => $tmp_luckybag_name[0],
                        "bm_ids" => $current_bm_ids,
                        "sku_num" => $luckybag_params[0],
                        "send_num" => $luckybag_params[1],
                        "price" => $luckybag_params[2],
                    );
                }else{
                    $err_msg = $tmp_luckybag_name[0]."格式错误";break;
                }
            }else{
                $err_msg = "福袋类关联物料信息错误";break;
            }
        }
        
        if($err_msg){
            return false;
        }else{
            if($params["retail_price"] != $luckybag_total_price){
                $err_msg = "售价和组合总和售价必须相等";return false;
            }
            $filter_luckybag_exists = array("lbr_name"=>$arr_lbr_name);
            if($edit){ //编辑
                $filter_luckybag_exists["sm_id|noequal"] = $params['sm_id'];
            }
            $mdl_material_luckybag_rules = app::get('material')->model('luckybag_rules');
            $rs_lbr = $mdl_material_luckybag_rules->getList("lbr_name",$filter_luckybag_exists);
            if(!empty($rs_lbr)){
                $exist_lbr_names = array();
                foreach($rs_lbr as $var_r_l){
                    $exist_lbr_names[] = $var_r_l["lbr_name"];
                }
                $err_msg = "组合：".implode(",",$exist_lbr_names)."已经存在";return false;
            }
            return $return_luckybag_rules;
        }
        
    }
    
    //检查填写的多选一信息格式内容
    private function check_pickone_content($params,&$err_msg,$edit=false){
        if(!is_numeric($params["retail_price"]) || $params["retail_price"]< 0){
            $err_msg = "销售价必须是大于等于0的数字";return false;
        }
        $pickone_bind_info = trim($params["pickone_bind_info"]);
        if(!$pickone_bind_info){
            $err_msg = "多选一类关联物料信息不能为空";return false;
        }
        $return_pickone_rules = array();
        $reg_number = "/^[1-9][0-9]*$/";
        $basicMaterialObj = app::get('material')->model('basic_material');
        //随机#a1:0|a2:0
        $tmp_pickone_rules = explode('#',$pickone_bind_info);
        if(count($tmp_pickone_rules) == 2){
            $select_type_arr = array("随机","排序");
            if(!in_array($tmp_pickone_rules[0],$select_type_arr)){
                $err_msg = "多选一类关联物料信息选择方式填写错误";return false;
            }
            $select_type = 1;
            if($tmp_pickone_rules[0] == "排序"){
                $select_type = 2;
            }
            $arr_pickone_data = explode("|",$tmp_pickone_rules[1]);
            if(count($arr_pickone_data) < 2){
                $err_msg = "多选一类关联物料信息中基础物料sku数量必须大于2种";return false;
            }
            $arr_basic_ma_bn = array();
            foreach($arr_pickone_data as $var_apd){
                $arr_pickone_item_data = explode(":",$var_apd);
                if(count($arr_pickone_item_data) != 2){
                    $err_msg = "多选一类关联物料信息中基础物料sku和排序值填写有误";break;
                }
                if(in_array($arr_pickone_item_data[0],$arr_basic_ma_bn)){
                    $err_msg = "多选一类关联物料信息中基础物料sku已重复";break;
                }
                $rs_bm_id = $basicMaterialObj->dump(array("material_bn"=>$arr_pickone_item_data[0]),"bm_id");
                if(empty($rs_bm_id)){
                    $err_msg = "多选一类关联物料信息中基础物料".$arr_pickone_item_data[0]."不存在";break;
                }
                $arr_basic_ma_bn[] = $arr_pickone_item_data[0];
                if(!is_numeric($arr_pickone_item_data[1])){
                    $err_msg = "多选一类关联物料信息中排序值必须是数值";break;
                }
                $return_pickone_rules[] = array(
                        "bm_id" => $rs_bm_id["bm_id"],
                        "sort" => $arr_pickone_item_data[1],
                        "select_type" => $select_type,
                );
            }
            if($err_msg){
                return false;
            }
        }else{
            $err_msg = "多选一类关联物料信息错误";return false;
        }
        return $return_pickone_rules;
    }
    
}