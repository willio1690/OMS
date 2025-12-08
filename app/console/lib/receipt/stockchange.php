<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/10/20 11:14:29
 * @describe: 库存异动
 * ============================
 */
class console_receipt_stockchange {

    /**
     * doAdjust
     * @param mixed $id ID
     * @return mixed 返回值
     */

    public function doAdjust($id) {
        $objSC = app::get('console')->model('wms_stock_change');
        $row = $objSC->db_dump($id);
        if(empty($row)) {
            return [true, ['msg'=>'缺少匹配明细，无需出库']];
        }
        if($row['adjust_status'] == '2') {
            return [true, ['msg'=>'已经调整完成']];
        }
        $material = app::get('material')->model('basic_material')->db_dump(['material_bn'=>$row['product_bn']], 'bm_id');
        if(empty($material)) {
            $msg = '缺少对应基础物料';
            $objSC->update(['adjust_msg'=>$msg], ['id'=>$id]);
            return [false, ['msg'=>$msg]];
        }
        $bd = $this->getBranchId($row['wms_node_id'], $row['warehouse']);
        $nomalItems = $defectiveItems = [];

        $batch= $batchs = [];
        if($row['batch_code']){
            $batch[] = array(
                'purchase_code' => $row['batch_code'],
                'produce_code'  => $row['produce_code'],
                'product_time'  => $row['product_date'] ? strtotime($row['product_date']) : 0,
                'expire_time'   => $row['expire_date'] ? strtotime($row['expire_date']) : 0,
                'normal_defective' => ($row['defective_num'] ? 'defective' : 'normal'),
                'num'        => $row['normal_num'] ? $row['normal_num'] : $row['defective_num'],
            );

            $batchs[$material['bm_id']] = json_encode($batch);
        }
        if($row['normal_num']) {
            if(empty($bd['negative_branch_id'])) {
                $msg = '缺少对应仓库';
                $objSC->update(['adjust_msg'=>$msg], ['id'=>$id]);
                return [false, ['msg'=>$msg]];
            }
            $nomalItems[$material['bm_id']] = $row['normal_num'];

        }
        if($row['defective_num']) {
            if(empty($bd['negative_cc_branch_id'])) {
                $msg = '缺少残损仓无法处理残品';
                $objSC->update(['adjust_msg'=>$msg], ['id'=>$id]);
                return [false, ['msg'=>$msg]];
            }
            $defectiveItems[$material['bm_id']] = $row['defective_num'];
        }
        $isFinish = true;
        $adjustMsg = '';
        if($defectiveItems) {
            $adjustData = [
                'source' => '异动单',
                'is_check' => '0',
                'iso_status' => 'confirm',
                'adjust_type' => 'yd',
                'adjust_bill_type' => '残品',
                'adjust_mode' => 'zl',
                'branch_id' => current($bd['negative_cc_branch_id']),
                'negative_branch_id' => $bd['negative_cc_branch_id'],
                'origin_id' => $row['id'],
                'origin_bn' => $row['unique_bn'],
                'business_bn' => $row['order_code'],
                'items' => $defectiveItems,
                'batch' => $batchs,
            ];
            list($rs, $rsData) = kernel::single('console_adjust')->dealSave($adjustData);
            if(!$rs && !strpos($rsData['msg'], '已经存在调整单')) {
                $isFinish = false;
            }
            $adjustMsg .= $adjustData['adjust_bill_type'].':'.$rsData['msg'];
        }
        if($nomalItems) {
            $adjustData = [
                'source' => '异动单',
                'is_check' => '0',
                'iso_status' => 'confirm',
                'adjust_type' => 'yd',
                'adjust_bill_type' => '良品',
                'adjust_mode' => 'zl',
                'branch_id' => current($bd['negative_branch_id']),
                'negative_branch_id' => $bd['negative_branch_id'],
                'origin_id' => $row['id'],
                'origin_bn' => $row['unique_bn'],
                'business_bn' => $row['order_code'],
                'items' => $nomalItems,
                'batch' => $batchs,
            ];
            list($rs, $rsData) = kernel::single('console_adjust')->dealSave($adjustData);
            if(!$rs && !strpos($rsData['msg'], '已经存在调整单')) {
                $isFinish = false;
            }
            $adjustMsg .= $adjustData['adjust_bill_type'].':'.$rsData['msg'];
        }
        $objSC->update(['adjust_status'=>($isFinish ? '2' : '1'),'adjust_msg'=>$adjustMsg], ['id'=>$id]);
        return [$isFinish, ['msg'=>$adjustMsg]];
    }


    /**
     * 获取BranchId
     * @param mixed $node_id ID
     * @param mixed $warehouse warehouse
     * @return mixed 返回结果
     */
    public function getBranchId($node_id, $warehouse) {
        $wmsModel = app::get('channel')->model('channel');
        $filter                 = array('node_id' => $node_id);
        $filter['channel_type'] = 'wms';
        $wms                    = $wmsModel->db_dump($filter, 'channel_id');
        $branch_relationObj = app::get('wmsmgr')->model('branch_relation');
        $branch_relation    = $branch_relationObj->getList('*', array('wms_branch_bn'=>$warehouse,'wms_id'=>$wms['channel_id']));
        $branchObj = kernel::single('console_iostockdata');
        $data = [];
        if($branch_relation) {
            uasort($branch_relation, [$this, 'cmp_by_negative']);
            foreach ($branch_relation as $v) {
                $branch_info = $branchObj->getBranchBybn($v['sys_branch_bn']);
                if($branch_info['type'] == 'damaged') {
                    $data['negative_cc_branch_id'][] = $branch_info['branch_id'];
                } else {
                    $data['negative_branch_id'][] = $branch_info['branch_id'];
                }
            }
        } else {
            $branch_info = $branchObj->getBranchBybn($warehouse);
            if($branch_info['wms_id'] == $wms['channel_id']) {
                if($branch_info['type'] == 'damaged') {
                    $data['negative_cc_branch_id'][] = $branch_info['branch_id'];
                } else {
                    $data['negative_branch_id'][] = $branch_info['branch_id'];
                }
            }
        }
        return $data;
    }

    /**
     * cmp_by_negative
     * @param mixed $a a
     * @param mixed $b b
     * @return mixed 返回值
     */
    public function cmp_by_negative($a, $b) {
        if($a['negative'] == $b['negative']) {
            return 0;
        }
        return $a['negative'] > $b['negative'] ? -1 : 1;
    }
}