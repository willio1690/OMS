<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 生成基础物料与销售物料
 *
 * @author XueDing@shopex.cn
 * @version 0.1
 */
class material_material
{
    
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params)
    {
        $ad_info                      = kernel::single('ome_func')->getDesktopUser();
        $_POST['sales_material_name'] = $params['material_name'];
        $_POST['sales_material_bn']   = $params['shop_product_bn'];
        $_POST['sales_material_type'] = isset($params['sales_material_type']) ? $params['sales_material_type'] : 1;
        $_POST['shop_id']             = $params['shop_id'] ? $params['shop_id'] : '_ALL_';
        $_POST['gen_mode']            = isset($params['gen_mode']) ? $params['gen_mode'] : 1;
        $_POST['retail_price']        = $params['shop_price'];
        $_POST['cost']                = $params['price'];
        $_POST['retail_price']        = $params['retail_price'];
        $_POST['weight']              = $params['weight'];
        $_POST['length']              = $params['length'];
        $_POST['width']               = $params['width'];
        $_POST['high']                = $params['high'];
        $_POST['unit']                = $params['unit'];
        $_POST['specifications']      = $params['specifications'];
        $_POST['color']               = $params['color'];
        $_POST['size']                = $params['size'];
        $_POST['banner']              = $params['banner'];
        $_POST['source']              = $params['source_from'];
        $_POST['channel_id']          = $params['channel_id'];
        $_POST['channel_name']        = $params['channel_name'];
        $_POST['sku_status']          = $params['sku_status'];
        $_POST['outer_product_id']    = $params['outer_product_id'];
        $_POST['op_id']               = $ad_info['op_id'];
        $_POST['op_name']             = $ad_info['op_name'];
        $res                          = $this->toAdd($err_msg);
        return ['res' => $res, 'err_msg' => $err_msg];
    }
    
    /**
     * 基础物料新增提交方法
     */
    function toAdd(&$err_msg)
    {
        //根据类型判断如果是自动的就自动生成基础物料，失败则提示
        if ($_POST['sales_material_type'] != 2 && $_POST['sales_material_type'] != 5 && $_POST['gen_mode'] == 1) {
            if (!($bm_id = $this->autoGenBasicMaterial($_POST, $err_msg))) {
                return false;
            } else {
                $_POST['bm_id'] = $bm_id;
            }
        }
        
        if (!$this->checkAddParams($_POST, $err_msg)) {
            return false;
        }
        
        $salesMaterialObj    = app::get('material')->model('sales_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        
        //保存物料主表信息
        $addData = array(
            'sales_material_name'     => $_POST['sales_material_name'],
            'sales_material_bn'       => $_POST['sales_material_bn'],
            'sales_material_bn_crc32' => $_POST['sales_material_bn_crc32'],
            'sales_material_type'     => $_POST['sales_material_type'],
            'shop_id'                 => $_POST['shop_id'],
            'create_time'             => time(),
        );
        $is_save = $salesMaterialObj->save($addData);
        
        if ($is_save) {
            $is_bind = false;
            //如果有关联物料就做绑定操作
            $salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
            //普通销售物料关联
            if (($_POST['sales_material_type'] == 1 || $_POST['sales_material_type'] == 3) && !empty($_POST['bm_id'])) {
                $addBindData = array(
                    'sm_id'  => $addData['sm_id'],
                    'bm_id'  => $_POST['bm_id'],
                    'number' => 1,
                );
                $salesBasicMaterialObj->insert($addBindData);
                
                $is_bind = true;
            } elseif ($_POST['sales_material_type'] == 2 && !empty($_POST['at'])) {
                //促销销售物料关联
                foreach ($_POST['at'] as $k => $v) {
                    $addBindData = array(
                        'sm_id'  => $addData['sm_id'],
                        'bm_id'  => $k,
                        'number' => $v,
                        'rate'   => $_POST['pr'][$k],
                    );
                    $salesBasicMaterialObj->insert($addBindData);
                    $addBindData = null;
                }
                
                $is_bind = true;
            } elseif ($_POST['sales_material_type'] == 5 && !empty($_POST['sort'])) { //多选一
                $mdl_ma_pickone_ru = app::get('material')->model('pickone_rules');
                $select_type       = $_POST["pickone_select_type"] ? $_POST["pickone_select_type"] : 1; //默认“随机”
                foreach ($_POST['sort'] as $key_bm_id => $val_sort) {
                    $current_insert_arr = array(
                        "sm_id"       => $addData['sm_id'],
                        "bm_id"       => $key_bm_id,
                        "sort"        => $val_sort ? $val_sort : 0,
                        "select_type" => $select_type,
                    );
                    $mdl_ma_pickone_ru->insert($current_insert_arr);
                }
                $is_bind = true;
            }
            
            //如果有绑定物料数据，设定销售物料为绑定状态
            if ($is_bind) {
                $salesMaterialObj->update(array('is_bind' => 1), array('sm_id' => $addData['sm_id']));
            }
            
            //保存销售物料扩展信息
            $addExtData = array(
                'sm_id'        => $addData['sm_id'],
                'cost'         => $_POST['cost'] ? $_POST['cost'] : 0.00,
                'retail_price' => $_POST['retail_price'] ? $_POST['retail_price'] : 0.00,
                'weight'       => $_POST['weight'] ? $_POST['weight'] : 0.00,
                'unit'         => $_POST['unit'],
            );
            $salesMaterialExtObj->insert($addExtData);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 添加基础物料
     * @param $params
     * @param $err_msg
     * @return bool|mixed
     */
    function autoGenBasicMaterial($params, &$err_msg)
    {
        $_POST = $params;
        
        $_POST['material_name']  = $params['sales_material_name'];
        $_POST['material_bn']    = $params['sales_material_bn'];
        $_POST['material_code']  = (trim($params['material_code']) ? $params['material_code'] : $params['sales_material_bn']);#基础物料条码必填项
        $_POST['retail_price']   = floatval($params['retail_price']);
        $_POST['serial_number']  = $params['serial_number'];#基础物料唯一码
        $_POST['type']           = 1;
        $_POST['visibled']       = 1;
        $_POST['specifications'] = $params['material_specification'];#物料规格
        
        $_POST['cost']   = floatval($params['cost']);#成本 价
        $_POST['weight'] = intval($params['weight']);#重量
        
        $_POST['cat_id'] = intval($params['goods_type']);#类型
        $_POST['brand']  = intval($params['brand_id']);#品牌
        $_POST['source'] = $params['source'];#来源
        
        if (!$this->checkAddBasicMParams($_POST, $err_msg)) {
            return false;
        }
        
        $basicMaterialObj           = app::get('material')->model('basic_material');
        $basicMaterialExtObj        = app::get('material')->model('basic_material_ext');
        $basicMaterialFeatureGrpObj = app::get('material')->model('basic_material_feature_group');
        $basicMaterialStockObj      = app::get('material')->model('basic_material_stock');
        $basicMaterialConfObj       = app::get('material')->model('basic_material_conf');
        $barocdeObj                 = app::get('material')->model('barcode');
        $sourceMappingObj           = app::get('material')->model('basic_material_channel');
        
        #检验保质期监控配置
        $use_expire = intval($_POST['use_expire']);
        $warn_day   = intval($_POST['warn_day']);
        $quit_day   = intval($_POST['quit_day']);
        if ($use_expire == 1 && ($warn_day <= $quit_day)) {
            $err_msg = '预警天数必须大于自动退出库存天数';
            kernel::database()->rollBack();
            return false;
        }
        
        //保存物料主表信息
        $addData = array(
            'material_name'     => $_POST['material_name'],
            'material_bn'       => $_POST['material_bn'],
            'material_bn_crc32' => $_POST['material_bn_crc32'],
            'type'              => $_POST['type'],
            'serial_number'     => $_POST['serial_number'],
            'visibled'          => $_POST['visibled'],
            'create_time'       => time(),
            'source'            => $_POST['source']
        );
        $is_save = $basicMaterialObj->save($addData);
        
        if ($is_save) {
            //保存物料渠道id映射关系
            if (isset($params['channel_id'])) {
                $mappingData = array(
                    'bm_id'            => $addData['bm_id'],
                    'material_bn'      => $addData['material_bn'],
                    'outer_product_id' => $params['outer_product_id'],
                    'channel_id'       => $params['channel_id'],
                    'channel_name'     => $params['channel_name'],
                    'approve_status'   => $params['sku_status'],
                    'op_id'            => $params['op_id'],
                    'op_name'          => $params['op_name'],
                    'price'            => $_POST['retail_price'] ? $_POST['retail_price'] : 0.00,
                    'create_time'      => time(),
                    'last_modify'      => time(),
                );
                $sourceMappingObj->insert($mappingData);
            }
            //保存条码信息
            $sdf = array(
                'bm_id' => $addData['bm_id'],
                'type'  => material_codebase::getBarcodeType(),
                'code'  => $_POST['material_code'],
            );
            $barocdeObj->insert($sdf);
            
            //保存保质期配置
            $useExpireConfData = array(
                'bm_id'       => $addData['bm_id'],
                'use_expire'  => $_POST['use_expire'] == 1 ? 1 : 2,
                'warn_day'    => $_POST['warn_day'] ? $_POST['warn_day'] : 0,
                'quit_day'    => $_POST['quit_day'] ? $_POST['quit_day'] : 0,
                'create_time' => time(),
            );
            $basicMaterialConfObj->save($useExpireConfData);
            
            //如果关联半成品数据
            if ($addData['type'] == 1) {
                $basicMaterialCombinationItemsObj = app::get('material')->model('basic_material_combination_items');
                if (isset($_POST['at'])) {
                    foreach ($_POST['at'] as $k => $v) {
                        $tmpChildMaterialInfo = $basicMaterialObj->dump($k, 'material_name,material_bn');
                        
                        $addCombinationData = array(
                            'pbm_id'            => $addData['bm_id'],
                            'bm_id'             => $k,
                            'material_num'      => $v,
                            'material_name'     => $tmpChildMaterialInfo['material_name'],
                            'material_bn'       => $tmpChildMaterialInfo['material_bn'],
                            'material_bn_crc32' => sprintf('%u', crc32($tmpChildMaterialInfo['material_bn'])),
                        );
                        $basicMaterialCombinationItemsObj->insert($addCombinationData);
                        $addCombinationData = null;
                    }
                }
            }
            
            //保存基础物料的关联的特性
            if ($_POST['ftgp_id']) {
                $addBindFeatureData = array(
                    'bm_id'            => $addData['bm_id'],
                    'feature_group_id' => $_POST['ftgp_id'],
                );
                $basicMaterialFeatureGrpObj->insert($addBindFeatureData);
                $addBindFeatureData = null;
            }
            
            //保存物料扩展信息
            $addExtData = array(
                'bm_id'          => $addData['bm_id'],
                'cost'           => $_POST['cost'] ? $_POST['cost'] : 0.00,
                'retail_price'   => $_POST['retail_price'] ? $_POST['retail_price'] : 0.00,
                'weight'         => $_POST['weight'] ? $_POST['weight'] : 0.00,
                'unit'           => $_POST['unit'],
                'specifications' => $_POST['specifications'],
                'brand_id'       => $_POST['brand_id'],
                'cat_id'         => $_POST['cat_id'],
                'length'         => $_POST['length'] ? $_POST['length'] : 0.00,
                'width'          => $_POST['width'] ? $_POST['width'] : 0.00,
                'high'           => $_POST['high'] ? $_POST['high'] : 0.00,
                'color'          => $_POST['color'],
                'size'           => $_POST['size'],
                'banner'         => $_POST['banner'],
            );
            
            $basicMaterialExtObj->insert($addExtData);
            
            //保存物料库存信息
            // * redis库存高可用，废弃掉直接修改db库存、冻结的方法
            $addStockData = array(
                'bm_id'        => $addData['bm_id'],
                // 'store'        => $_POST['store'] ? $_POST['store'] : 0,
                // 'store_freeze' => $_POST['store_freeze'] ? $_POST['store_freeze'] : 0,
                'store'        => 0,
                'store_freeze' => 0,
            );
            $basicMaterialStockObj->insert($addStockData);
            
            return $addData['bm_id'];
        } else {
            $err_msg = '保存失败';
            return false;
        }
    }
    
    /**
     * 销售物料新增时的参数检查方法
     * 
     * @param Array $params
     * @param String $err_msg
     * @return Boolean
     */
    function checkAddParams(&$params, &$err_msg)
    {
        if (empty($params['sales_material_name']) || empty($params['sales_material_bn'])) {
            $err_msg = "必填信息不能为空";
            return false;
        }
        
        $salesMaterialObj  = app::get('material')->model('sales_material');
        $salesMaterialInfo = $salesMaterialObj->getList('sales_material_bn',
            array('sales_material_bn' => $params['sales_material_bn']));
        if ($salesMaterialInfo) {
            $err_msg = "当前新增的物料编码已被使用，不能重复";
            return false;
        }
        
        $params['sales_material_bn_crc32'] = sprintf('%u', crc32($params['sales_material_bn']));
        
        if ($params['sales_material_type'] == 2) {
            if (!isset($params['at'])) {
                $err_msg = "组合物料请至少设置一个物料明细内容";
                return false;
            }
            
            foreach ($params['at'] as $val) {
                if (count($params['at']) == 1) {
                    if ($val < 2) {
                        $err_msg = "只有一种物料时，数量必须大于1";
                        return false;
                    }
                } else {
                    if ($val < 1) {
                        $err_msg = "数量必须大于0";
                        return false;
                    }
                }
            }
            
            foreach ($params['pr'] as $val) {
                $tmp_rate += $val;
            }
            
            if ($tmp_rate > 100) {
                $err_msg = "分摊销售价合计百分比:" . $tmp_rate . ",已超100%";
                return false;
            } elseif ($tmp_rate < 100) {
                $err_msg = "分摊销售价合计百分比:" . $tmp_rate . ",不足100%";
                return false;
            }
        }
        
        //多选一
        if ($params['sales_material_type'] == 5) {
            if (!$params['pickone_select_type']) {
                $err_msg = "缺少多选一的选择方式";
                return false;
            }
            if (!isset($params['sort']) || count($params['sort']) < 2) {
                $err_msg = "多选一物料请至少设置二个基础物料明细内容";
                return false;
            }
            $reg_number = "/^[1-9][0-9]*$/"; //正整数
            foreach ($params['sort'] as $key_bm_id => $var_w) {
                if ($var_w) {
                    if (!preg_match($reg_number, $var_w)) {
                        $err_msg = "权重必须是数值";
                        return false;
                    }
                } else { //0和空 数据库默认给0
                }
            }
        }
        
        return true;
    }
    
    /**
     * 增加基础物料验证
     * @param $params
     * @param $err_msg
     * @return bool
     */
    function checkAddBasicMParams(&$params, &$err_msg)
    {
        //检查物料名称
        if (empty($params['material_name']) || empty($params['material_bn'])) {
            $err_msg = "必填信息不能为空";
            return false;
        }
        //检查物料编号
        $basicMaterialObj  = app::get('material')->model('basic_material');
        $basicMaterialInfo = $basicMaterialObj->dump(array('material_bn' => $params['material_bn']),
            'bm_id,material_bn');
        if ($basicMaterialInfo) {
            $sourceMappingObj = app::get('material')->model('basic_material_channel');
            //保存物料渠道id映射关系
            if (isset($params['channel_id'])) {
                //当前记录
                $bm_source_mapping = $sourceMappingObj->dump(array(
                    'material_bn' => $params['material_bn'],
                    'channel_id'  => $params['channel_id']
                ), 'bm_id,channel_id,approve_status');
                $mappingData       = array(
                    'bm_id'            => $basicMaterialInfo['bm_id'],
                    'material_bn'      => $basicMaterialInfo['material_bn'],
                    'outer_product_id' => $params['outer_product_id'],
                    'channel_id'       => $params['channel_id'],
                    'channel_name'     => $params['channel_name'],
                    'approve_status'   => $params['sku_status'],
                    'create_time'      => time(),
                    'last_modify'      => time(),
                    'op_id'            => $params['op_id'],
                    'op_name'          => $params['op_name'],
                    'price'            => $params['retail_price'] ? $params['retail_price'] : 0.00,
                );
                if ($bm_source_mapping) {
                    $mappingUpData = array(
                        'outer_product_id' => $params['outer_product_id'],
                        'approve_status'   => $params['sku_status'],
                        'price'            => $params['retail_price'] ? $params['retail_price'] : 0.00,
                        'last_modify'      => time(),
                    );
                    $sourceMappingObj->update($mappingUpData, [
                        'material_bn' => $params['material_bn'],
                        'channel_id'  => $params['channel_id']
                    ]);
                } else {
                    $sourceMappingObj->insert($mappingData);
                }
                //是否有上架状态
                $approve_status = $sourceMappingObj->getList('bm_id,channel_id', array(
                    'material_bn'    => $params['material_bn'],
                    'approve_status' => '1',
                ));
                if (count($approve_status) > 1) {
	                $sourceMappingObj->update(['is_error'=>'1','last_modify'=>time()],
		                ['material_bn' => $params['material_bn'],'approve_status'=>'1']);
                } else {
	                $sourceMappingObj->update(['is_error'=>'0','last_modify'=>time()],
		                ['material_bn' => $params['material_bn']]);
                }
            }
            $err_msg = "当前新增的物料编码已存在，不能重复创建";
            return false;
        }
        //检查物料条码
        $barcode = app::get('material')->model('barcode')->getList('bm_id',
            array('code' => $params['material_code'], 'type' => material_codebase::getBarcodeType()));
        if ($basicMaterialInfo) {
            $err_msg = "当前新增的物料条码已被使用，不能重复使用";
            return false;
        }
        
        $params['material_bn_crc32'] = sprintf('%u', crc32($params['material_bn']));
        
        if ($params['type'] == 1) {
            if (isset($params['at'])) {
                foreach ($params['at'] as $val) {
                    if ($val < 1) {
                        $err_msg = "数量必须大于0";
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
}