<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 销售物料的队列任务导入最终执行Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 0.1
 */

class material_sales_material_to_import
{
    /**
     * 销售物料关联条码的队列任务执行
     * 
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */

    function run(&$cursor_id,$params,&$errmsg)
    {
        $importObj    = app::get($params['app'])->model($params['mdl']);
        $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $salesMaterialShopFreezeObj = app::get('material')->model('sales_material_shop_freeze');

        $dataSdf    = $params['sdfdata'];
        foreach ($dataSdf as $v){
            //销售物料主表sales_material
            $importData = array(
                    'sales_material_name' => $v['sales_material_name'],
                    'sales_material_bn' => $v['sales_material_bn'],
                    'sales_material_bn_crc32' => $v['sales_material_bn_crc32'],
                    'shop_id' => $v['shop_id'],
                    'sales_material_type' => $v['sales_material_type'],
                    'is_bind' => $v['is_bind'],
                    'create_time' => time(),
                    'disabled' => 'false',
            );
            $is_save    = $importObj->save($importData);

            if($is_save)
            {
                $is_bind = false;

                //如果有关联物料就做绑定操作
                if(($v['sales_material_type'] == 1 || $v['sales_material_type'] == 3 || $v['sales_material_type'] == 6) && !empty($v['bind_bm_id']))
                {
                    //普通或赠品销售物料关联
                    $addBindData = array(
                                        'sm_id' => $importData['sm_id'],
                                        'bm_id' => $v['bind_bm_id'],
                                        'number' => 1,
                                    );
                    $salesBasicMaterialObj->insert($addBindData);
                    $bm_id = $v['bind_bm_id'];
                    $is_bind = true;
                }
                elseif(($v['sales_material_type'] == 2) && !empty($v['bind_bm_id']))
                {
                    //促销销售物料关联
                    foreach($v['bind_bm_id'] as $bm_key => $bm_val)
                    {
                        $addBindData = array(
                                            'sm_id' => $importData['sm_id'],
                                            'bm_id' => $bm_key,
                                            'number' => $bm_val['number'],
                                            'rate' => (string)$bm_val['rate'],
                                        );
                        $salesBasicMaterialObj->insert($addBindData);

                        $addBindData = null;
                    }
                    $bm_id = $bm_key;

                    $is_bind = true;
                }elseif($v['sales_material_type'] == 4 && !empty($v['lbr'])){ //福袋
                    $mdl_material_luckbag_rules = app::get('material')->model('luckybag_rules');
                    foreach($v['lbr'] as $var_lbr){
                        $addBindData = array(
                                "lbr_name" => $var_lbr["name"],
                                "sm_id" => $importData['sm_id'],
                                "bm_ids" => implode(",", $var_lbr["bm_ids"]),
                                "sku_num" => $var_lbr["sku_num"],
                                "send_num" => $var_lbr["send_num"],
                                "price" => $var_lbr["single_price"],
                        );
                        $mdl_material_luckbag_rules->insert($addBindData);
                    }
                    $bm_id = $var_lbr["bm_ids"];
                    $is_bind = true;
                }elseif($v['sales_material_type'] == 5 && !empty($v['sort'])){ //多选一
                    $mdl_ma_pickone_ru = app::get('material')->model('pickone_rules');
                    $select_type = $v["pickone_select_type"]; //默认1为“随机” 2为排序
                    foreach($v['sort'] as $key_bm_id => $val_sort){
                        $current_insert_arr = array(
                                "sm_id" => $importData['sm_id'],
                                "bm_id" => $key_bm_id,
                                "sort" => $val_sort ? $val_sort : 0,
                                "select_type" => $select_type,
                        );
                        $mdl_ma_pickone_ru->insert($current_insert_arr);
                    }
                    $bm_id = $key_bm_id;
                    $is_bind = true;
                }

                //如果有绑定物料数据，设定销售物料为绑定状态
                if($is_bind){
                    $importObj->update(array('is_bind'=>1), array('sm_id'=>$importData['sm_id']));
                }
                if($v['brand_id']) {
                    $brand_id = $v['brand_id'];
                } else {
                    $brandInfo = app::get('material')->model('basic_material_ext')->db_dump(['bm_id'=>$bm_id], 'brand_id');
                    $brand_id = $brandInfo['brand_id'];
                }
                //保存销售物料扩展信息
                $addExtData = array(
                        'sm_id' => $importData['sm_id'],
                        'cost' => floatval($v['cost']),
                        'retail_price' => floatval($v['retail_price']),
                        'weight' => floatval($v['weight']),
                        'unit' => $v['unit'],
                        'brand_id' => $brand_id,
                );
                $salesMaterialExtObj->insert($addExtData);

                //保存销售物料店铺级冻结
                if($v['shop_id'] != '_ALL_')
                {
                    $addStockData = array(
                            'sm_id' => $importData['sm_id'],
                            'shop_id' => $v['shop_id'],
                            'shop_freeze' => 0,
                    );
                    $salesMaterialShopFreezeObj->insert($addStockData);
                }
            }else{
                $m = $importObj->db->errorinfo();
                if(!empty($m)){
                    $errmsg.=$m.";";
                }
            }
         }

         return false;
    }
}
