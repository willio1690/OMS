<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/8 17:20:23
 * @describe: 就近原则获取仓库
 * ============================
 */
class omeauto_branch_router extends omeauto_branch_abstract {
    private $orderBn;
    private $location;

    /**
     * 处理
     * @param mixed $branchIds ID
     * @param mixed $group group
     * @param mixed $branchInfo branchInfo
     * @return mixed 返回值
     */

    public function process($branchIds, &$group, $branchInfo) {
        $orders = $group->getOrders();
        $order = current($orders);
        $this->orderBn = $order['order_bn'];
        $shipArea = $order['ship_area'];
        list(,,$areaId) = explode(':', $shipArea);
        if(empty($areaId)) {
            return $branchIds;
        }
        $bgData = $group->getBranchGroup();
        if($bgData) {
            $branchIds = array();
            foreach ($bgData as $k => $v) {
                $branchIds = array_merge($branchIds, $v['branch_id']);
            }
            $branchIds = array_unique($branchIds);
        }
        $branchMdl = app::get('ome')->model('branch');
        $field = 'branch_id,branch_bn,name,area,location,b_type';
        $branchInfo = $branchMdl->getList($field, array('branch_id'=>$branchIds,'check_permission' => 'false'), 0, -1, 'weight desc');
        list($rs, $this->location) = kernel::single('console_map_order')->getLocation($order['order_id'], false);
        if($rs) {
            $branchInfo = $this->_getLocationBranch($branchInfo);
        } else {
            $branchInfo = $this->_getGroupBranch($areaId, $branchInfo);
        }
        $branchIds = array();
        foreach ($branchInfo as $v) {
            $branchIds[] = $v['branch_id'];
        }
        if($bgData) {
            foreach ($bgData as $k => $v) {
                if(!array_intersect($branchIds, $v['branch_id'])) {
                    unset($bgData[$k]);
                    continue;
                }
            }
            $group->setBranchGroup($bgData);
        }
        return $branchIds;
    }

    protected function _getGroupBranch($areaId, $branchInfo){
        if(!$this->_groupAreaId[$areaId]) {
            $this->_groupAreaId[$areaId] = $this->_getGroupAreaId($areaId);
        }
        $groupBranch = array(
            'distinct' => array(),
            'city' => array(),
            'province' => array(),
            'logistics' => array(),
            'province_router' => array(),
            'country' => array(),
        );
        $logisticsBranchId = array();
        // 预取门店仓覆盖区域，避免在循环中重复查询
        $storeBranchIds = array();
        foreach ((array)$branchInfo as $bi) {
            if (isset($bi['b_type']) && intval($bi['b_type']) == 2) {
                $storeBranchIds[] = intval($bi['branch_id']);
            }
        }
        // bid => [lastRegionId, ...]
        $bid2LastRegionIds = array();
        // 收集所有末级覆盖region_id用于一次性查询其region_grade
        $allLastRegionIds = array();
        if (!empty($storeBranchIds)) {
            $warehouseObj = app::get('logisticsmanager')->model('warehouse');
            $warehouseList = $warehouseObj->getList('branch_id,region_ids', array('branch_id' => $storeBranchIds));
            foreach ((array)$warehouseList as $wh) {
                $bid = intval($wh['branch_id']);
                if (empty($wh['region_ids'])) {
                    continue;
                }
                // 多组覆盖以分号分隔，每组为逗号分隔的路径，取每组最后一个ID
                $groups = array_filter(array_map('trim', explode(';', $wh['region_ids'])));
                foreach ($groups as $grp) {
                    $pathIds = array_filter(array_map('intval', explode(',', $grp)));
                    if (empty($pathIds)) {
                        continue;
                    }
                    $lastId = end($pathIds);
                    $bid2LastRegionIds[$bid][] = $lastId;
                    $allLastRegionIds[$lastId] = true;
                }
            }
        }
        // 查询这些末级region的等级grade，构建映射
        $regionId2Grade = array();
        if (!empty($allLastRegionIds)) {
            $regionModel = app::get('eccommon')->model('regions');
            $regionRows = $regionModel->getList('region_id,region_grade', array('region_id' => array_keys($allLastRegionIds)));
            foreach ((array)$regionRows as $row) {
                $regionId2Grade[intval($row['region_id'])] = intval($row['region_grade']);
            }
        }
        // 订单地区路径（index为级数，value为该级的region_id），在_getGroupAreaId中已提供
        $areaIdRegionPath = (array)$this->_groupAreaId[$areaId]['areaIdregionPath'];
        foreach ($branchInfo as $val) {
            if($val['b_type'] == 2) {
                // 门店仓：先判断是否配置覆盖区域；未配置直接归入 country 并跳过
                $bid = intval($val['branch_id']);
                if (empty($bid2LastRegionIds[$bid])) {
                    $groupBranch['country'][] = $val;
                    continue;
                }
                // 覆盖区域存在：按覆盖路径级数与订单路径同级ID一致判定可配送
                $covered = false;
                foreach ($bid2LastRegionIds[$bid] as $lastId) {
                    $grade = isset($regionId2Grade[$lastId]) ? $regionId2Grade[$lastId] : 0;
                    if ($grade > 0 && isset($areaIdRegionPath[$grade]) && intval($areaIdRegionPath[$grade]) === intval($lastId)) {
                        $covered = true;
                        break;
                    }
                }
                if (!$covered) {
                    // 覆盖不支持：归入 country 并跳过
                    $groupBranch['country'][] = $val;
                    continue;
                }
                // 覆盖支持：不中断，继续按常规分级逻辑处理
            }
            
            list(,$areaTxt,$areaBranchId) = explode(':', $val['area']);
            if(in_array($areaBranchId, $this->_groupAreaId[$areaId]['distinctAreaId'])) {
                $groupBranch['distinct'][] = $val;
                continue;
            }
            if(in_array($areaBranchId, $this->_groupAreaId[$areaId]['cityAreaId'])) {
                $groupBranch['city'][] = $val;
                continue;
            }
            if(in_array($areaBranchId, $this->_groupAreaId[$areaId]['provinceAreaId'])) {
                $groupBranch['province'][] = $val;
                continue;
            }
            if($this->_groupAreaId[$areaId]['routerLogisticsArea']) {
                list($province, $city, $distinct) = explode('/', $areaTxt);
                $index = array_search($province, $this->_groupAreaId[$areaId]['routerLogisticsArea']);
                if($index !== false) {
                    $logisticsBranchId[$index][] = $val;
                    continue;
                }
            } else {
                if (in_array($areaBranchId, $this->_groupAreaId[$areaId]['logisticsAreaId'])) {
                    $groupBranch['logistics'][] = $val;
                    continue;
                }
            }
            $groupBranch['country'][] = $val;
        }
        ksort($logisticsBranchId);
        $groupBranch['province_router'] = $logisticsBranchId;
        foreach ($groupBranch as $key => $value) {
            if($key == 'province_router') {
                if($logisticsBranchId) {
                    $return = current($logisticsBranchId);
                    break;
                }
                continue;
            }
            if(count($value)) {
                $return = $value;
                break;
            }
        }
        $this->writeSuccessLog($groupBranch, $return);
        return $return;
    }

    protected function _getGroupAreaId($areaId) {
        $modelRegion = app::get('eccommon')->model('regions');
        $row = $modelRegion->db_dump(array('region_id'=>$areaId), 'region_path');
        $arrPath = explode(',', $row['region_path']);
        $province = $arrPath[1];
        $city = $arrPath[2];
        $distinct = $arrPath[3];
        $routerProvinceId = app::get('logistics')->model('area_router')->db_dump(array('area_id'=>$province), 'first_dc,router_area');
        $logisticsAreaId = array();
        $routerLogisticsArea = array();
        if($routerProvinceId) {
            $routerArea = unserialize($routerProvinceId['router_area']);
            $firstPId = '';
            foreach ($routerArea as $k => $val) {
                if(!$firstPId) {
                    $firstPId = $k;
                }
                if($val['weight'] < 0) {
                    continue;
                }
                $routerLogisticsArea[] = $val['name'];
            }
            $distinctAreaId = $cityAreaId = $provinceAreaId = array();
            if($firstPId == $province) {
                $distinctAreaId = $this->_getAreaId($distinct);
                $cityAreaId = $this->_getAreaId($city, $distinct);
                $provinceAreaId = $this->_getAreaId($province, $city);
            }
        } else {
            $distinctAreaId = $this->_getAreaId($distinct);
            $cityAreaId = $this->_getAreaId($city, $distinct);
            $provinceAreaId = $this->_getAreaId($province, $city);
            $logistics = app::get('logistics')->model('area')->db_dump(
                array('filter_sql' => 'find_in_set(' . $province . ', region_id)'), 'region_id'
            );
            if ($logistics) {
                $arrLogistics = explode(',', $logistics['region_id']);
                foreach ($arrLogistics as $val) {
                    if ($val != $province) {
                        $tmp = $this->_getAreaId($val);
                        $logisticsAreaId = array_merge($logisticsAreaId, $tmp);
                    }
                }
            }
        }
        return array(
            'distinctAreaId' => $distinctAreaId,
            'cityAreaId' => $cityAreaId,
            'provinceAreaId' => $provinceAreaId,
            'logisticsAreaId' => $logisticsAreaId,
            'first_dc' => $firstDc,
            'routerLogisticsArea' => $routerLogisticsArea,
            'areaIdregionPath' => $arrPath, // 县区id对应的完整路径数组
        );
    }

    protected function _getAreaId($find, $notFind = 0) {
        $filterSql = 'find_in_set(' . $find . ', region_path)';
        if($notFind) {
            $filterSql .= ' and !find_in_set(' . $notFind . ', region_path)';
        }
        $filter = array(
            'haschild' => '0',
            'filter_sql' => $filterSql
        );
        $modelRegion = app::get('eccommon')->model('regions');
        $rows = $modelRegion->getList('region_id', $filter);
        $return = array();
        foreach ($rows as $val) {
            $return[] = $val['region_id'];
        }
        return $return;
    }

    protected function _getLocationBranch($branchInfo) {
        $gBranch = [];
        $msg = [];
        $orderLocation = $this->location;
        $orderLocation = explode(',', $orderLocation);
        foreach ($branchInfo as $v) {
            if(!$v['location']) {
                list($rs, $v['location']) = kernel::single('console_map_branch')->getLocation($v['branch_id'], false);
                if(!$rs) {
                    $msg['get location fail'][] = $v;
                    continue;
                }
            }
            $branchLocation = explode(',', $v['location']);
            $length = bcadd(
                bcmul(($branchLocation[0] - $orderLocation[0]), ($branchLocation[0] - $orderLocation[0]), 12), 
                bcmul(($branchLocation[1] - $orderLocation[1]), ($branchLocation[1] - $orderLocation[1]), 12),
                12
            );
            $length = (string) $length;
            $gBranch[$length][] = $v;
        }
        ksort($gBranch);
        $msg['result'] = current($gBranch);
        $this->writeSuccessLog($gBranch, $msg);
        return $msg['result'];
    }

    protected function writeSuccessLog($result, $msg) {
        foreach ($result as $k => $v) {
            $result[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        foreach ($msg as $k => $v) {
            $msg[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        $msg['订单定位'] = $this->location;
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();
        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => '就近优先结果',
            'status'        => 'success',
            'worker'        => '',
            'params'        => json_encode(array('branch.router', $result), JSON_UNESCAPED_UNICODE), #longtext
            'msg'           => json_encode($msg, JSON_UNESCAPED_UNICODE), #text json字符串
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $this->orderBn,
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => '',
        );
        $apilogModel->insert($logsdf);
    }
}