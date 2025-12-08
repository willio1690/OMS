<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店的队列任务导入最终执行Lib类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: import.php 2016-07-26 15:00
 */
class o2o_store_to_import
{
    /**
     * 门店导入的队列任务执行
     * 
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */

    function run(&$cursor_id, $params, &$errmsg)
    {
        $storeLib        = kernel::single('o2o_store');
        $dataSdf         = $params['sdfdata'];
        
        foreach ($dataSdf as $val)
        {
            #门店数据处理
            $result    = $storeLib->create_store($val, $errmsg);
            if(!$result)
            {
                return false;
            }
            
            // 处理覆盖区域
            if (!empty($val['coverage_area'])) {
                $this->saveCoverageArea($val, $errmsg);
            }
        }
        
        return false;
    }
    
    /**
     * 保存门店覆盖区域
     * @param array $storeData 门店数据
     * @param string &$errmsg 错误信息
     * @return bool
     */
    private function saveCoverageArea($storeData, &$errmsg)
    {
        try {
            // 获取门店信息
            $storeMdl = app::get('o2o')->model('store');
            $store = $storeMdl->dump(array('store_bn' => $storeData['org_no']), 'store_id,store_bn,branch_id');
            
            if (!$store) {
                $errmsg .= "门店[{$storeData['org_no']}]不存在，无法保存覆盖区域。";
                return false;
            }
            
            // 获取仓库信息
            $branchMdl = app::get('ome')->model('branch');
            $branch = $branchMdl->dump(array('branch_id' => $store['branch_id'], 'check_permission' => 'false'), 'branch_bn');
            
            if (!$branch) {
                $errmsg .= "门店[{$storeData['org_no']}]关联仓库不存在，无法保存覆盖区域。";
                return false;
            }
            
            $warehouseMdl = app::get('logisticsmanager')->model('warehouse');
            
            // 检查是否已存在覆盖区域设置
            $existingWarehouse = $warehouseMdl->dump(array('branch_bn' => $store['store_bn'], 'b_type' => 2), 'id');
            
            $coverageArea = $storeData['coverage_area'];
            $regionIds = array();
            $regionNames = array();
            
            // 处理全国情况
            if (in_array('CN', $coverageArea)) {
                $regionNames[] = '中国';
                // 获取所有省份ID
                $regionsMdl = app::get('eccommon')->model('regions');
                $provinces = $regionsMdl->getList('region_id', array('region_grade' => 1, 'source' => 'systems'));
                foreach ($provinces as $prov) {
                    $regionIds[] = (string)$prov['region_id'];
                }
            } else {
                // 处理具体区域
                foreach ($coverageArea as $regionPath) {
                    $pathParts = explode(',', $regionPath);
                    $lastRegionId = end($pathParts);
                    
                    // 查询区域名称
                    $regionsMdl = app::get('eccommon')->model('regions');
                    $region = $regionsMdl->dump(array('region_id' => $lastRegionId), 'local_name');
                    if ($region) {
                        $regionNames[] = $region['local_name'];
                    }
                    $regionIds[] = $regionPath;
                }
            }
            
            $warehouseData = array(
                'branch_id' => $store['branch_id'],
                'branch_bn' => $branch['branch_bn'],
                'warehouse_name' => 'STORE_' . $store['store_bn'],
                'region_ids' => implode(';', $regionIds),
                'region_names' => implode(',', $regionNames),
                'create_time' => time(),
                'warn_num' => 5,
                'operator' => 'system',
                'b_type' => 2,
            );
            
            if ($existingWarehouse) {
                // 更新
                $warehouseMdl->update($warehouseData, array('id' => $existingWarehouse['id']));
            } else {
                // 新增
                $warehouseMdl->insert($warehouseData);
            }
            
            return true;
            
        } catch (Exception $e) {
            $errmsg .= "保存覆盖区域失败：" . $e->getMessage();
            return false;
        }
    }
}
