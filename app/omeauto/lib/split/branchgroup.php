<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/10 17:23:35
 * @describe: 按仓库分组拆
 * ============================
 */
class omeauto_split_branchgroup extends omeauto_split_abstract {

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
        if(empty($sdf['split_config']['branchgroup'])) {
            return array(false, '仓库分组未选择');
        }
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
        $arrBranchId = $group->getBranchId();
        if(!is_array($arrBranchId)) {
            return array(false, '无多个仓库，无法拆单');
        }
        $group->setConfirmBranch(true);
        $tidBranchId = array();
        foreach ($arrBranchId as $k => $v) {//选择第一个仓库规则
            list($tmpTid, ) = explode('-', $k);
            if(!isset($tid)) {
                $tid = $tmpTid;
                $branchConfig = $group->getAutoBranch();
                $group->setAutoBranch((array)$branchConfig[$tid]);
            }
            if($tid == $tmpTid) {
                $tidBranchId[] = $v;
            }
        }
        $bgResult = $this->_getBranchGroup($tidBranchId, $splitConfig, $group);
        if($bgResult['rsp'] != 'succ') {
            return array(false, '仓库分组不满足条件:' . $bgResult['msg']);
        }
        $bgData = $bgResult['data'];
        $group->setBranchGroup($bgData);
        kernel::single('omeauto_branch_choose')->getSelectBid($tid,$group);
        $bgData = $group->getBranchGroup();
        foreach ($bgData as $v) {
            $group->setBranchId(array(reset($v['branch_id'])));
            break;
        }
        $group->setConfirmBranch(true);
        return array(true);
    }

    protected function _getBranchGroup($arrBranchId, $splitConfig, &$group) {
        $arrOrder = $group->getOrders();
        $bmIdNum = array();
        foreach ($arrOrder as $k => $order) {
            foreach ($order['objects'] as $ok => $object) {
                foreach ($object['items'] as $ik => $item) {
                    if($item['nums'] <= $item['split_num']) {
                        unset($arrOrder[$k]['objects'][$ok]['items'][$ik]);
                    }
                    $arrOrder[$k]['objects'][$ok]['items'][$ik]['original_num'] = $item['nums'];
                    $arrOrder[$k]['objects'][$ok]['items'][$ik]['nums'] = $nums = $item['nums'] - $item['split_num'];
                    if($bmIdNum[$item['product_id']]) {
                        $bmIdNum[$item['product_id']] += $nums;
                    } else {
                        $bmIdNum[$item['product_id']] = $nums;
                    }
                }
                if(empty($arrOrder[$k]['objects'][$ok]['items'])) {
                    unset($arrOrder[$k]['objects'][$ok]);
                }
            }
            if(empty($arrOrder[$k]['objects'])) {
                unset($arrOrder[$k]);
            }
        }
        if(empty($arrOrder)) {
            return array('rsp'=>'fail', 'msg'=>'已经拆完，无需拆单');
        }
        $group->updateOrderInfo($arrOrder);
        $arrOrderId = array();
        foreach ($arrOrder as $order) {
            $arrOrderId[] = $order['order_id'];
        }
        $group->setSplitOrderId($arrOrderId);
        $branchMdl = app::get('ome')->model('branch');
        $modelBp = app::get('ome')->model('branch_product');
        $bpFilter = array(
            'product_id' => array_keys($bmIdNum),
            'branch_id' => $arrBranchId,
            'filter_sql' => 'store>store_freeze'
        );
        $storeRows = $modelBp->getList('branch_id, product_id, store, store_freeze', $bpFilter);
        if(empty($storeRows)) {
            $group->setOrderStatus('*', omeauto_auto_const::__STORE_CODE);
            return array('rsp'=>'fail', 'msg'=>'有库存的仓库不存在');
        }
        $branchInfo = array();
        foreach ($storeRows as $val) {
            $validNum = $val['store'] > $val['store_freeze'] ? $val['store'] - $val['store_freeze'] : 0;
            if($validNum) {
                $branchInfo[$val['branch_id']]['branch_id'] = $val['branch_id'];
                $branchInfo[$val['branch_id']]['store'][$val['product_id']] = $validNum;
            }
        }
        $branchGroup = app::get('omeauto')->model('branchgroup')->getList('*', array('bg_id'=>$splitConfig['branchgroup']));
        if(empty($branchGroup)) {
            return array('rsp'=>'fail', 'msg'=>'没有仓库分组');
        }
        $bgData = array();
        $storeMaxGroup = array();
        foreach ($branchGroup as $v) {
            $bgBranchId = explode(',', $v['branch_group']);
            $bgOrderBid = array();
            foreach ($bgBranchId as $vBid) {
                $index = array_search($vBid, $arrBranchId);
                if($index !== false) {
                    $bgOrderBid[$index] = $vBid;
                }
            }
            if(!$bgOrderBid) {
                continue;
            }
            ksort($bgOrderBid);
            $tmpBgData = array('bg_id'=>$v['bg_id'],'name'=>$v['name'].'('.$v['bg_id'].')');
            $tmpBmNum = $bmIdNum;
            $hasBmId = array();
            foreach ($bgOrderBid as $bId) {
                if(empty($branchInfo[$bId])) {
                    continue;
                }
                foreach ($branchInfo[$bId]['store'] as $bmId => $num) {
                    if($tmpBmNum[$bmId] > 0) {
                        $hasBmId[$bmId] = $bmId;
                        $tmpBgData['branch_id'][$bId] = $bId;
                        if($num < $tmpBmNum[$bmId]) {
                            $tmpBmNum[$bmId] -= $num;
                            $tmpBgData['branch_product'][$bId][$bmId] = $num;
                        } else {
                            $tmpBgData['branch_product'][$bId][$bmId] = $tmpBmNum[$bmId];
                            unset($tmpBmNum[$bmId]);
                        }
                    } elseif(isset($tmpBmNum[$bmId])) {
                        unset($tmpBmNum[$bmId]);
                    }
                }
            }
            if($tmpBmNum) {
                if(count($hasBmId)) {
                    $storeMaxGroup[count($hasBmId)][] = $v['bg_id'];
                }
            } else {
                $storeMaxGroup['all'][] = $v['bg_id'];
            }
            $bgData[$v['bg_id']] = $tmpBgData;
        }
        if($storeMaxGroup['all']) {
            $maxGroup = $storeMaxGroup['all'];
        } elseif($storeMaxGroup) {
            ksort($storeMaxGroup);
            $maxGroup = end($storeMaxGroup);
        } else {
            return array('rsp'=>'fail', 'msg'=>'没有有库存的仓库分组');
        }
        foreach ($bgData as $k => $v) {
            if(!in_array($k, $maxGroup)) {
                unset($bgData[$k]);
            }
        }
        if(empty($bgData)) {
            return array('rsp'=>'fail', 'msg'=>'没有满足条件的仓库分组');
        }
        return array('rsp'=>'succ', 'data'=>$bgData);

    }
}