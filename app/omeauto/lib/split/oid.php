<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/6/24 14:51:08
 * @describe: 京东拆子单
 * ============================
 */
class omeauto_split_oid extends omeauto_split_abstract {

    #拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */

    public function getSpecial() {
        return array();
    }

    #拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf) {
        return array(true, '保存成功');
    }

    #拆分订单
    /**
     * splitOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function splitOrder(&$group, $splitConfig){
        $arrOrder = $group->getOrders();
        $group->setConfirmBranch(true);
        $arrOrderId = array();
        foreach ($arrOrder as $k => $order) {
            $arrOrderId[] = $order['order_id'];
            if($order['createway'] != 'matrix' || $order['shop_type'] != '360buy') {
                return array(false, '仅支持京东线上订单');
            }
            foreach ($order['objects'] as $ok => $object) {
                if(empty($object['oid'])) {
                    return [false, '订单有赠品或新增的商品，不能在进行子订单拆'];
                }
                $deleteObj = false;
                foreach ($object['items'] as $ik => $item) {
                    if ($item['nums'] > $item['split_num']) {
                        if($item['split_num'] > 0) {
                            return [false, '订单已进行数量拆分，不能在进行子订单拆'];
                        }
                    } else {
                        $deleteObj = true;
                        unset($arrOrder[$k]['objects'][$ok]['items'][$ik]);
                    }
                }
                if($deleteObj) {
                    if ($arrOrder[$k]['objects'][$ok]['items']) {
                        return [false, '订单已拆分子商品，不能在进行子订单拆'];
                    }
                    unset($arrOrder[$k]['objects'][$ok]);
                }
            }
            if (empty($arrOrder[$k]['objects'])) {
                unset($arrOrder[$k]);
            }
        }
        if (empty($arrOrder)) {
            return array(false, '可拆单明细为空，无需拆分');
        }
        foreach ($arrOrder as $key => $order) { //取第一个订单进行拆分
            break;
        }
        list($rs, $msg) = $this->_dealOidSplit($order, $group);
        if(!$rs) {
            return [false, $msg];
        }
        $splitOrder = array();
        $splitOrder[$key] = $order;
        if($arrOrderId) {
            $group->setSplitOrderId($arrOrderId);
        }
        $group->updateOrderInfo($splitOrder);
        if (empty($splitOrder)) {
            return array(false, '无法拆单');
        }
        return array(true);
    }

    protected function _dealOidSplit(&$order, &$group) {
        // 判断是否存在发货单
        $deliveryMdl           = app::get('ome')->model('delivery');
        $deliveryOrderMdl      = app::get('ome')->model('delivery_order');
        $filter = array(
            'delivery_id'  => array(),
            'status|notin' => array('back','cancel','return_back'),
            'parent_id'    => '0',
        );
        foreach ($deliveryOrderMdl->getList('*',array('order_id'=>$order['order_id'])) as $value) {
            $filter['delivery_id'][] = $value['delivery_id'];
        }
        if ($filter['delivery_id']) {
            if($deliveryMdl->getList('delivery_id',$filter)) {
                return [false, '已经生成发货单，不能再进行子单拆'];
            }
        }
        $arrBranchId = $group->getBranchId();
        if (!is_array($arrBranchId)) {
            //手工批量审单 固定仓库
            $bi = app::get('ome')->model('branch')->db_dump($arrBranchId, 'b_type,b_status');
            if ($bi['b_type'] == '2') {
                return array(false, '批量审单不支持门店');
            }
            $arrBranchId = array($arrBranchId);
            $storeRows   = $this->getStoreRows($arrBranchId, $order);
        } else {
            $tidBranchId = array();
            foreach ($arrBranchId as $k => $v) {
                //选择第一个仓库规则
                if(strpos($k, '-')) {
                    list($tmpTid) = explode('-', $k);
                } else {
                    $tmpTid = 0;
                }
                if (!isset($tid)) {
                    $tid          = $tmpTid;
                    $branchConfig = $group->getAutoBranch();
                    $group->setAutoBranch((array) $branchConfig[$tid]);
                }
                if ($tid == $tmpTid) {
                    $tidBranchId[] = $v;
                }
            }
            $storeRows = $this->getStoreRows($tidBranchId, $order);
        }
        if (empty($storeRows)) {
            $group->setOrderStatus('*', omeauto_auto_const::__STORE_CODE);
            return array(false, '有库存的仓库不存在');
        }
        //选取sku最全的仓库
        $maxBranch  = array();
        $branchInfo = array();
        foreach ($storeRows as $val) {
            $validNum = $val['store'] > $val['store_freeze'] ? $val['store'] - $val['store_freeze'] : 0;
            if ($validNum) {
                $maxBranch[$val['branch_id']] += 1;
                $branchInfo[$val['branch_id']]['branch_id']                 = $val['branch_id'];
                $branchInfo[$val['branch_id']]['store'][$val['product_id']] = $validNum;
            }
        }
        if (empty($maxBranch)) {
            $group->setOrderStatus('*', omeauto_auto_const::__STORE_CODE);
            return array(false, '有可用库存的仓库不存在');
        }
        $rs = $this->dealStoreOrder($order, $branchInfo);
        if(empty($rs['branchInfo'])) {
            $group->setOrderStatus('*', omeauto_auto_const::__STORE_CODE);
            return [false, '库存不足无法拆出子单'];
        }
        $canBranchId = [];
        foreach ($rs['is_split'] as $branchId => $value) {
            if(!$value) {
                $canBranchId[] = $branchId;
            }
        }
        if(!$canBranchId) {
            arsort($rs['sku_num']);
            $max = current($rs['sku_num']);
            $group->setSplitNotDly(true);
            foreach ($rs['sku_num'] as $branchId => $num) {
                if($max == $num) {
                    $canBranchId[] = $branchId;
                }
            }
        }
        if(count($canBranchId) == 1) {
            $branchId = current($canBranchId);
        } else {
            $group->updateOrderInfo([$order]);
            $group->setBranchId($canBranchId);
            $branchId = kernel::single('omeauto_branch_choose')->getSelectBid($tid, $group, $rs['branchInfo']);
        }
        $logResult = [
            '初始值' => $branchInfo,
            '拆分情况' => $rs,
            '初选仓' => $canBranchId,
            '终选仓' => $branchId
        ];
        $this->writeSuccessLog($logResult, $order);
        $group->setBranchId(array($branchId));
        $order = $rs['order'][$branchId];
        return [true];
    }

    protected function getStoreRows($arrBranchId, $order)
    {
        $bmIds = [];
        foreach ($order['objects'] as $ok => $object) {
            foreach ($object['items'] as $ik => $item) {
                $bmIds[] = $item['product_id'];
            }
        }
        $modelBp  = app::get('ome')->model('branch_product');
        $bpFilter = array(
            'product_id' => $bmIds,
            'branch_id'  => $arrBranchId,
            'filter_sql' => 'store>store_freeze',
        );
        $storeRows = $modelBp->getList('branch_id, product_id, store, store_freeze', $bpFilter);
        return $this->sortBranchById($storeRows, $arrBranchId);
    }

    #storeRows 根据 arrBranchId 排序
    protected function sortBranchById($storeRows, $arrBranchId)
    {
        if (empty($storeRows)) {
            return array();
        }
        $tmpStore = array();
        foreach ($storeRows as $v) {
            $index = array_search($v['branch_id'], $arrBranchId);
            if ($index !== false) {
                $tmpStore[$index][] = $v;
            }
        }
        ksort($tmpStore);
        $ttmpStore = array();
        foreach ($tmpStore as $v) {
            foreach ($v as $vv) {
                $ttmpStore[] = $vv;
            }
        }
        return $ttmpStore;
    }

    #根据库存订单处理拆分
    protected function dealStoreOrder($order, $branchInfo) {
        $return = ['branchInfo'=>[], 'order'=>[], 'sku_num'=>[], 'is_split'=>[]];
        $psRow = app::get('ome')->model('order_platformsplit')->db_dump(['order_id'=>$order['order_id']], 'id');
        foreach ($branchInfo as $branch_id => $value) {
            $tmpBI = ['branch_id'=>$branch_id, 'store'=>[]];
            $tmpSN = 0;
            $is_split = $psRow ? true : false;
            $tmpOrder = $order;
            foreach ($tmpOrder['objects'] as $ok => $object) {
                $isEnough = true;
                foreach ($object['items'] as $ik => $item) {
                    if($value['store'][$item['product_id']] < $item['nums']) {
                        $isEnough = false;
                        break;
                    }
                }
                if($isEnough) {
                    foreach ($object['items'] as $ik => $item) {
                        $value['store'][$item['product_id']] -= $item['nums'];
                        $tmpBI['store'][$item['product_id']] += $item['nums'];
                        $tmpSN += $item['nums'];
                    }
                } else {
                    unset($tmpOrder['objects'][$ok]);
                    $is_split = true;
                }
            }
            if($tmpOrder['objects']) {
                $return['branchInfo'][$branch_id] = $tmpBI;
                $return['order'][$branch_id] = $tmpOrder;
                $return['sku_num'][$branch_id] = $tmpSN;
                $return['is_split'][$branch_id] = $is_split;
            }
        }
        return $return;
    }

    protected function writeSuccessLog($logResult, $order)
    {
        $bmIdBn  = array();
        $arrOrderBn = array();
        foreach ($order['objects'] as $ok => $object) {
            foreach ($object['items'] as $ik => $item) {
                $bmIdBn[$item['product_id']] = $item['bn'];
            }
        }
        $arrOrderBn[] = $order['order_bn'];
        $branchId = array();
        foreach ($logResult['初始值'] as $v) {
            $branchId[] = $v['branch_id'];
        }
        $branchLib  = kernel::single('ome_interface_branch');
        $branchRows = $branchLib->getList('branch_id, branch_bn', array('branch_id' => $branchId));
        $branchIdBn = array();
        foreach ($branchRows as $v) {
            $branchIdBn[$v['branch_id']] = $v['branch_bn'];
        }
        $logResult['id对应编码'] = [
            '基础物料'=>$bmIdBn,
            '仓库'=>$branchIdBn,
        ];
        $apilogModel = app::get('ome')->model('api_log');
        $log_id      = $apilogModel->gen_id();
        $logsdf      = array(
            'log_id'        => $log_id,
            'task_name'     => '按京东子订单拆结果',
            'status'        => 'success',
            'worker'        => '',
            'params'        => serialize(array('store.split', $logResult)), #longtext
            'msg'           => '', #text json字符串
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $arrOrderBn[0],
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => '',
        );
        $apilogModel->insert($logsdf);
    }
}