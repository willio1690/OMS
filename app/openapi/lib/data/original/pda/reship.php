<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @describe pda售后退换货相关
 * @author pangxp
 */
class openapi_data_original_pda_reship{
    
    public function getList($filter, $offset = 0, $limit = 100)
    {
        // 搜索条件全为空 不返回查询列表
        if ( empty($filter['logi_no']) && empty($filter['order_bn']) && empty($filter['ship_name']) && empty($filter['ship_mobile']) && empty($filter['member_uname']) ) {
            return array(
                'state' => 1, // 0成功 1失败
                'message' => '必填项不能为空',
            );
        }
        $data = array(); 
        $count = 0;
        $oReship = app::get('ome')->model('reship');
        $oOrders = app::get('ome')->model('orders');
        if ( !empty($filter['order_bn']) ) {
            $order_bn = $filter['order_bn'];
            $orderData = $oOrders->getList('order_id', array('order_bn' => $filter['order_bn']));
            $filter['order_id'] = $orderData[0]['order_id'];
            unset($filter['order_bn']);
            $count = $oReship->count($filter);
            $reshipData = $oReship->getList('reship_id, reship_bn, order_id, ship_name, ship_tel, ship_mobile, is_check', $filter, $offset, $limit);
            foreach ($reshipData as &$reshipItem) {
                $reshipItem['order_bn'] = $order_bn;
            }
            return array(
                'state' => 0, // 0成功 1失败
                'lists' => $reshipData,
                'count' => $count,
            );
        }
        if ( !empty($filter['logi_no']) ) {
            // 退货单明细SKU维度上物流单号优先级最高 明细上没有再用退货单上的
            // $oReshipItems = app::get('ome')->model('reship_items');
            // $reshipItemsData = $oReshipItems->getList('reship_id, item_id', array('return_logi_no' => $filter['logi_no']));
            // if ($reshipItemsData) {
            //     $reship_ids = $reship_item_ids = array();
            //     foreach ($reshipItemsData as $item) {
            //         $reship_ids[] = $item['reship_id'];
            //         $reship_item_ids[$item['reship_id']] = $item['item_id'];
            //     }
            //     $filter['reship_id'] = $reship_ids;
            //     unset($filter['logi_no']);
            // } else {
                $filter['return_logi_no'] = $filter['logi_no'];
                unset($filter['logi_no']);
            // }
        }
        $count = $oReship->count($filter);
        $reshipData = $oReship->getList('reship_id, reship_bn, order_id, ship_name, ship_tel, ship_mobile, is_check', $filter, $offset, $limit);
        if (!empty($reshipData)) {
            $orderids = array();
            foreach ($reshipData as $value) {
                $orderids[] = $value['order_id'];
            }
            $orderData = $oOrders->getList('order_id, order_bn', array('order_id|in' => $orderids));
            $orderids = array();
            foreach ($orderData as $item) {
                $orderids[$item['order_id']] = $item['order_bn'];
            }
            // 标记order_bn(订单号)
            foreach ($reshipData as &$reshipItem) {
                $reshipItem['order_bn'] = $orderids[$reshipItem['order_id']];
                if (isset($reship_item_ids[$reshipItem['reship_id']])) {
                    $reshipItem['item_id'] = $reship_item_ids[$reshipItem['reship_id']];
                }
            }
        }
        return array(
            'state' => 0, // 0成功 1失败
            'lists' => $reshipData,
            'count' => $count,
        );
        
    }

    public function getDetailList($filter, $offset = 0, $limit = 100)
    {
        $data = array(); 
        $count = 0;
        #退换货明细
        $oReshipItems = app::get('ome')->model('reship_items');
        $count = $oReshipItems->count($filter);
        $reshipData = $oReshipItems->getList('item_id, reship_id, product_id, product_name, bn, branch_id, num, normal_num, defective_num', $filter);

        $branchIds = $productIds = $material = $orderInfo = array();

        foreach ($reshipData as $value) {
            $branchIds[] = $value['branch_id'];
            $productIds[] = $value['product_id'];
        }

        $oBranch = app::get('ome')->model('branch');
        $oBasicMaterialExt = app::get('material')->model('basic_material_ext');
        $branchInfo = app::get('ome')->model('branch')->getList('branch_id, name', array('branch_id|in' => $branchIds));
        $productInfo = app::get('material')->model('basic_material_ext')->getList('bm_id, specifications', array('bm_id|in' => $productIds));
        // $materialInfo = app::get('material')->model('basic_material')->getList('bm_id, delivery_factory, material_supplier, bn72code', array('bm_id|in' => $productIds));
        // foreach ($materialInfo as $value) {
        //     $material[$value['bm_id']]['delivery_factory'] = $value['delivery_factory'];
        //     $material[$value['bm_id']]['material_supplier'] = $value['material_supplier'];
        //     $material[$value['bm_id']]['bn72code'] = $value['bn72code'];
        // }
        $branchIds = $productIds = array();
        foreach ($branchInfo as $val) {
            $branchIds[$val['branch_id']] = $val['name'];
        }
        foreach ($productInfo as $val) {
            $productIds[$val['bm_id']] = $val['specifications'];
        }
        $orderData = kernel::database()->select('SELECT r.order_id, r.return_id, reship_bn, product_id, t_begin, memo, sendnum, return_num FROM `sdb_ome_reship` r LEFT JOIN `sdb_ome_order_items` i ON r.order_id=i.order_id WHERE reship_id=' . $filter['reship_id']);
        foreach ($orderData as $value) {
            $orderInfo[$value['product_id']] = $value; 
        }
        // 获取下售后申请原因
        if (!empty($orderData[0]['return_id'])) {
            $oReturnProduct = app::get('ome')->model('return_product');
            $return_product_info = $oReturnProduct->getList('return_id, content, memo', array('return_id' => $orderData[0]['return_id']));
        }
        foreach ($reshipData as &$item) {
            $item['reship_bn'] = $orderInfo[$item['product_id']]['reship_bn'];
            $item['specifications'] = $productIds[$item['product_id']];
            // $item['delivery_factory'] = $material[$item['product_id']]['delivery_factory'];
            // $item['supplier'] = $material[$item['product_id']]['material_supplier'];
            // $item['bn'] = substr($material[$item['product_id']]['bn72code'], 8); // bn改成返回72码
            $item['arrival_time'] = '';
            $item['registration_time'] = $orderInfo[$item['product_id']]['t_begin'];
            $item['branch_name'] = $branchIds[$item['branch_id']];
            $item['remark'] = $orderInfo[$item['product_id']]['memo'];
            $item['sendnum'] = $orderInfo[$item['product_id']]['sendnum'];
            $item['return_num'] = $orderInfo[$item['product_id']]['return_num'];
            $item['content'] = urlencode($return_product_info[0]['content']);
            $item['memo'] = urlencode($return_product_info[0]['memo']);

        }

        return array(
            'state' => 0, // 0成功 1失败
            'lists' => $reshipData,
            'count' => $count,
        );

    }

    public function normalReturn($data)
    {
        $data['data'] = json_decode($data['data'], 1);
        if (empty($data['data'])) {
            return array(
                'state' => 1,
                'message' => '正常退货时data不能为空',
            );
        }
        $reshipItemData = $returnNum = array();
        $oOrderItems = app::get('ome')->model('order_items');
        $oReship = app::get('ome')->model('reship');
        $order = $oReship->getList('reship_id, reship_bn, order_id, is_check', array('reship_id' => $data['reship_id']));
        if ( empty($order) ) {
            return array(
                'state' => 1,
                'message' => '数据库中退货单不存在',
            );
        }
        if ( in_array($order[0]['is_check'], array('5', '7', '11')) ) {
            return array(
                'state' => 1,
                'message' => '待确认或已拒绝或已完成的退换单不能再退货',
            );
        }
        $oReshipItems = app::get('ome')->model('reship_items');
        $tmp = $oReshipItems->getList('item_id, reship_id, product_id, bn, normal_num, defective_num, num', array('reship_id' => $data['reship_id'], 'return_type' => 'return'));
        if ( empty($tmp) ) {
            return array(
                'state' => 1,
                'message' => '数据库中退货单明细不存在',
            );
        }
        foreach ($tmp as $v) {
            $reshipItemData[$data['reship_id']][$v['product_id']]['item_id'] = $v['item_id'];
            $reshipItemData[$data['reship_id']][$v['product_id']]['num'] = $v['num'];
            $reshipItemData[$data['reship_id']][$v['product_id']]['normal_num'] = $v['normal_num'];
            $reshipItemData[$data['reship_id']][$v['product_id']]['bn'] = $v['bn'];
        }
        $oReshipPDAReturnInfo = app::get('ome')->model('reship_pda_return_info');
        $db = kernel::database();
        $transaction = $db->beginTransaction();

        foreach ($data['data'] as $reshipItem) {
            $quantity = intval($reshipItem['quantity']);
            $item = array();
            if ( empty($reshipItem['product_id']) || !in_array($reshipItem['abnormalBit'], array('0', '1')) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '商品id|异常状态不能为空',
                );
            }
            if ( $reshipItem['abnormalBit'] === '1' && empty($reshipItem['reason']) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '当AbnormalBit参数为1时，异常原因不能为空',
                );
            }

            // print_r(array($quantity, $reshipItemData, $data, $reshipItem));
            // var_dump( array($quantity, $reshipItemData[$data['reship_id']][$reshipItem['product_id']]['normal_num'], $reshipItemData[$data['reship_id']][$reshipItem['product_id']]['num']) );
            // exit();

            if ( ($quantity + intval($reshipItemData[$data['reship_id']][$reshipItem['product_id']]['normal_num'])) > intval($reshipItemData[$data['reship_id']][$reshipItem['product_id']]['num']) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '退货数量不能大于申请数量',
                );
            }

            $filter = array(
                'reship_id' => $data['reship_id'], 
                'product_id' => $reshipItem['product_id'],
            );

            $op_info = kernel::single('ome_func')->getDesktopUser();
            // 存储退货信息
            $item = array(
                'item_id' => $reshipItemData[$data['reship_id']][$reshipItem['product_id']]['item_id'],
                'reship_id' => $data['reship_id'], 
                'product_id' => $reshipItem['product_id'],
                'defective_num' => 0,
                'normal_num' => $quantity,
                'remark' => $reshipItem['remark'],
                'abnormalbit' => $reshipItem['abnormalBit'],
                'reason' => $reshipItem['reason'],
                'scenepicture' => json_encode($reshipItem['scenePicture']),
                'number' => $data['number'],  // pda退货序列号
                'return_branch_id'      => isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : '',  // 退货仓库ID  子账号和仓库id一一对应（约定的）
                'op_id' => $op_info['op_id'],
                'op_name' => $op_info['op_name'],
                'add_time' => time(),
            );
            // print_r($item);exit();
            $saveRes = $oReshipPDAReturnInfo->save($item);
            if (!$saveRes) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '保存pda退货信息失败',
                );
            }

            if ($reshipItem['abnormalBit'] === '0') {
                $returnNum[$reshipItem['product_id']] = $quantity;
            }

        }

        //判断退的数量
        $flag = true;
        foreach ($tmp as $tval){
            if ( intval($tval['num']) != intval($returnNum[$tval['product_id']]) + intval($tval['normal_num']) ){
                $flag = false;
                break;
            }
        }

        $updateSdf = array(
            'status'    => $flag ? 'FINISH' : 'PARTIN',
            'reship_bn' => $order[0]['reship_bn'],
            'order_id'  => $order[0]['order_id'],
            'source'    => 'pda',
        );

        //退货明细
        $reshipItems = array();
        foreach ($data['data'] as $v) {
            if ( $v['abnormalBit'] === '1' ) {
                //良品
                $normalNum    = 0;
            } else {
                //良品
                $normalNum    = intval($v['quantity']);
            }

            $reshipItems[] = array(
                'bn'             => $reshipItemData[$data['reship_id']][$v['product_id']]['bn'],
                'normal_num'     => $normalNum,
                'defective_num'  => 0,  // pda过来的 不良品不用管 恒置0
                'return_branch_id'      => isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : '',  // 退货仓库ID  子账号和仓库id一一对应（约定的）
            );
        }
        $updateSdf['items'] = $reshipItems;

        $rs = kernel::single('console_event_receive_iostock')->reship_result($updateSdf,);
        if ($rs['rsp'] == 'succ') {
            $op_info = kernel::single('ome_func')->getDesktopUser();
            $oOperation_log = app::get('ome')->model('operation_log');
            $oOperation_log->write_log('reship@ome', $data['reship_id'], 'pda正常退货,序列号' . $data['number'], time(), array('op_id' => $op_info['op_id'], 'op_name' => $op_info['op_name']));
            $db->commit();
            return array('state' => 0, 'data' => $updateSdf);
        } else {
            $db->rollBack();

            return array(
                'state' => 1,
                'message' => $rs['msg'],
            );
        }

    }

    public function abnormalReturn($data)
    {
        $reshipItemData = $returnNum = array();
        $oReship = app::get('ome')->model('reship');
        $order = $oReship->getList('reship_id, reship_bn, order_id, is_check', array('reship_id' => $data['reship_id']));
        if ( empty($order) ) {
            return array(
                'state' => 1,
                'message' => '数据库中退货单不存在',
            );
        }
        if ( in_array($order[0]['is_check'], array('5', '7')) ) {
            return array(
                'state' => 1,
                'message' => '已拒绝或已完成的退换单不能再退货',
            );
        }
        $oReshipItems = app::get('ome')->model('reship_items');
        $tmp = $oReshipItems->getList('item_id, reship_id, product_id, bn', array('reship_id' => $data['reship_id'], 'return_type' => 'return'));
        if ( empty($tmp) ) {
            return array(
                'state' => 1,
                'message' => '数据库中退货单明细不存在',
            );
        }
        $oReshipPDAReturnInfo = app::get('ome')->model('reship_pda_return_info');
        foreach ($tmp as $v) {
            $reshipItemData[$data['reship_id']][$v['product_id']]['item_id'] = $v['item_id'];
            $reshipItemData[$data['reship_id']][$v['product_id']]['bn'] = $v['bn'];
        }
        $data['data'] = json_decode($data['data'], 1);
        if ( empty($data['data']) ) {
            return array(
                'state' => 1,
                'message' => 'data不能为空',
            );
        }
        $db = kernel::database();
        $transaction = $db->beginTransaction();
        foreach ($data['data'] as $reshipItem) {
            $item = array();
            if ( empty($reshipItem['product_id']) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '商品id不能为空',
                );
            }
            if ( !isset($reshipItemData[$data['reship_id']][$reshipItem['product_id']]['item_id']) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '商品id错误',
                );
            }
            if ( empty($reshipItem['reason']) ) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '异常原因不能为空',
                );
            }

            $filter = array(
                'reship_id' => $data['reship_id'], 
                'product_id' => $reshipItem['product_id'],
            );

            $op_info = kernel::single('ome_func')->getDesktopUser();
            // 存储退货信息
            $item = array(
                'item_id' => $reshipItemData[$data['reship_id']][$reshipItem['product_id']]['item_id'],
                'reship_id' => $data['reship_id'], 
                'product_id' => $reshipItem['product_id'],
                'defective_num' => intval($reshipItem['quantity']),
                'normal_num' => 0,
                'remark' => $reshipItem['remark'],
                'abnormalbit' => '1',
                'reason' => $reshipItem['reason'],
                'scenepicture' => json_encode($reshipItem['scenePicture']),
                'number' => $data['number'],  // pda退货序列号
                'op_id' => $op_info['op_id'],
                'op_name' => $op_info['op_name'],
                'add_time' => time(),
            );
            $saveRes = $oReshipPDAReturnInfo->save($item);
            if (!$saveRes) {
                $db->rollBack();
                return array(
                    'state' => 1,
                    'message' => '保存pda退货信息失败',
                );
            }

        }

        $updateSdf = array(
            'status'    => 'abnormal',
            'reship_id' => $data['reship_id'],
            'source' => 'pda',
        );

        $rs = kernel::single('console_receipt_reship')->abnormal($updateSdf, $msg);
        if ($rs) {
            $op_info = kernel::single('ome_func')->getDesktopUser();
            $oOperation_log = app::get('ome')->model('operation_log');
            $oOperation_log->write_log('reship@ome', $data['reship_id'], 'pda异常退货', time(), array('op_id' => $op_info['op_id'], 'op_name' => $op_info['op_name']));
            $db->commit();
            return array('state' => 0, 'data' => $updateSdf);
        } else {
            $db->rollBack();
            return array(
                'state' => 1,
                'message' => $rs['msg'],
            );
        }
    }

    public function forward($params)
    {
        $oReship = app::get('ome')->model('reship');
        $oReshipItems = app::get('ome')->model('reship_items');
        $transaction = $oReship->db->beginTransaction();
        $filter = array('reship_id' => $params['reship_id']);
        $reshipUpdateRes = $oReship->update(array('forward' => '1'), $filter);
        if (!$reshipUpdateRes) {
            $oReship->db->rollBack();
            return array(
                'state' => 1,
                'message' => '退货单打标转寄失败!',
            );
        }
        $filter['item_id'] = $params['item_id'];
        $reshipItemsUpdateRes = $oReshipItems->update(array('forward' => '1'), $filter);
        if (!$reshipItemsUpdateRes) {
            $oReship->db->rollBack();
            return array(
                'state' => 1,
                'message' => '退货单明细打标转寄失败!',
            );
        }
        $oReship->db->commit($transaction);
        return array('state' => 0, 'data' => $params, 'message' => 'pda操作转寄:' . $params['bn'] . '成功！');
    }


}