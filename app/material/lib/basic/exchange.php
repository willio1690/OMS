<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料转换Lib类
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_basic_exchange{

     function __construct(){
        $this->_basicMaterialObj = app::get('material')->model('basic_material');
        $this->_basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $this->_salesMaterialObj = app::get('material')->model('sales_material');
     }

    /**
     * 
     * 根据传入的基础物料生成相应的销售
     * @param String $ids
     * @return Array $res
     */

    public function process($ids){
        $result = array('total' => 0, 'succ' => 0, 'fail' => 0);

        if(empty($ids)){
            return $result;
        }

        $total = 0;
        $succ = 0;
        $fail = 0;

        //获取选中的基础物料信息
        $basicMList = $this->_basicMaterialObj->getList('bm_id,material_name,material_bn,material_bn_crc32,type,tax_rate,tax_name,tax_code', array('bm_id'=>explode(',', $ids)), 0 ,-1);
        if($basicMList){
            $basicMBns = array();
            $basciMBnIds = array();
            $basciMInfos = array();
            foreach($basicMList as $k =>$basic){
                
                //转义物料名称中的'单引号
                $basic['material_name'] = addslashes($basic['material_name']);
                $sales_material_type = 1;
                if ($basic['type'] == 4){
                    $sales_material_type = 6;
                }
                $basicMBns[] = $basic['material_bn'];
                $basciMBnIds[$basic['material_bn']] = $basic['bm_id'];
                $basciMInfos[$basic['material_bn']] = "('".$basic['material_name']."','".$basic['material_bn']."','".$basic['material_bn_crc32']."','".$sales_material_type."','".$basic['tax_rate']."','".$basic['tax_name']."','".$basic['tax_code']."','".time()."')";
            }
        }


        //获取对应基础物料的扩展信息
        $basicMExtList = $this->_basicMaterialExtObj->getList('bm_id,retail_price,unit,brand_id', array('bm_id'=>explode(',', $ids)), 0 ,-1);
        if($basicMExtList){
            $basciMExtInfos = array();
            foreach($basicMExtList as $k =>$basicExt){
                $basciMExtInfos[$basicExt['bm_id']] = $basicExt;
            }
        }

        $total = count($basicMList);

        //如果已存在该销售物料货号则不处理
        $salesMList = $this->_salesMaterialObj->getList('sales_material_bn', array('sales_material_bn'=>$basicMBns), 0 ,-1);
        if($salesMList){
            foreach($salesMList as $k =>$sales){
                if(isset($basciMInfos[$sales['sales_material_bn']])){
                    //从数据数组中去除已存在的
                    unset($basciMInfos[$sales['sales_material_bn']]);
                    unset($basciMBnIds[$sales['sales_material_bn']]);

                    $flag = array_search($sales['sales_material_bn'], $basicMBns);
                    if($flag >=0){
                        unset($basicMBns[$flag]);
                    }

                    $fail++;
                }
            }
        }
        unset($basicMList,$basicMExtList,$salesMList);

        //新增销售物料插入
        if(count($basciMInfos) > 0){
            $sql = 'INSERT INTO `sdb_material_sales_material` (`sales_material_name`,`sales_material_bn`,`sales_material_bn_crc32`,sales_material_type,`tax_rate`,`tax_name`,`tax_code`,`create_time`) VALUES';
            $sql .= implode(',', $basciMInfos);
            $exec_res = $this->_salesMaterialObj->db->exec($sql);
            if($exec_res){
                $salesMList = $this->_salesMaterialObj->getList('sm_id,sales_material_bn', array('sales_material_bn'=>$basicMBns), 0 ,-1);
                if($salesMList){
                    $salesM_ids = array();
                    foreach($salesMList as $k => $salesM){
                        if(isset($basciMBnIds[$salesM['sales_material_bn']])){
                            $now_bm_id = $basciMBnIds[$salesM['sales_material_bn']];
                            $values[] = "('".$salesM['sm_id']."','".$now_bm_id."','1')";
                            $salesM_ids[] = $salesM['sm_id'];

                            if(isset($basciMExtInfos[$now_bm_id])){
                                $sales_values[] = "('".$salesM['sm_id']."','".$basciMExtInfos[$now_bm_id]['retail_price']."','".$basciMExtInfos[$now_bm_id]['unit']."','".$basciMExtInfos[$now_bm_id]['brand_id']."')";
                            }
                        }
                    }

                    //初始化销售物料的扩展信息
                    if($sales_values){
                        $sql3 = 'INSERT INTO `sdb_material_sales_material_ext` (`sm_id`,`retail_price`,`unit`,`brand_id`) VALUES';
                        $sql3 .= implode(',', $sales_values);
                        $this->_salesMaterialObj->db->exec($sql3);
                    }

                    //物料绑定关系添加
                    if($values){
                        $sql2 = 'INSERT INTO `sdb_material_sales_basic_material` (`sm_id`,`bm_id`,`number`) VALUES';
                        $sql2 .= implode(',', $values);
                        $exec2_res = $this->_salesMaterialObj->db->exec($sql2);
                        if($exec2_res){
                            $this->_salesMaterialObj->update(array('is_bind'=>1),array('sm_id'=>$salesM_ids));
                            $succ = $total - $fail;
                        }
                    }
                }
            }
        }

        return array('total' => $total, 'succ' => $succ, 'fail' => $fail);
    }

}