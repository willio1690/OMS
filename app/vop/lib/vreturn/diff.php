<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_vreturn_diff
{
    private const  _VRETURN_PAGE_SIZE = 5;

    /**
     * 获取ReturnPageSize
     * @return mixed 返回结果
     */
    public function getReturnPageSize()
    {
        return self::_VRETURN_PAGE_SIZE;
    }

    /**
     * 获取PullCount
     * @param mixed $params 参数
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getPullCount($params, $shop_id)
    {
        $params['pageIndex']    = 1;
        $params['pageSize']     = 1;

        // 可返回多个退供单号
        $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getReturnDiffInterList($params);


        if ($rsp_data['rsp'] != 'succ') {
            return [false, $rsp_data['msg']];
        }
        
        $data = $rsp_data['data'];
        if (!$data['total']) {
            return [false, '退供差异单单头不存在'];
        }

        return [true,'成功', $data['total']];
    }

    /**
     * 获取PullPageIndex
     * @param mixed $params 参数
     * @param mixed $shop_id ID
     * @param mixed $pageIndex pageIndex
     * @return mixed 返回结果
     */
    public function getPullPageIndex($params, $shop_id, $pageIndex = 1)
    {
        $pageSize = $this->getReturnPageSize();

        $params['pageIndex']    = $pageIndex;
        $params['pageSize']     = $pageSize;

        // 可返回多个退供单号
        $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getReturnDiffInterList($params);

        if ($rsp_data['rsp'] != 'succ') {
            return [false, $rsp_data['msg']];
        }

        $data = $rsp_data['data'];

        if (!$data['total']) {
            return [false, '退供差异单单头不存在'];
        }

        if (!$data['deatil_list']) {
            return [false, sprintf('退供差异单单头查询第[%s]页为空', $pageIndex)];
        }

        // 循环处理
        $err_msg = [];
        foreach ($data['deatil_list'] as $detail) {
            list($result, $msg) = $this->processVReturnDiff($detail, $shop_id);

            if (!$result) {
                $err_msg[] = $msg;
            }
        }

        return [true, '成功'.($err_msg ? '，但有错误：'.implode('；', $err_msg) : ''), $data['total']];
    }

    /**
     * 下载采购订单列表
     * @param array $filter 条件
     * @param string $shop_id
     * @return array
     */
    public function getPullList($params, $shop_id, $pageIndex = 1)
    {
        $pageSize = $this->getReturnPageSize();
      
        $msgArr = [];
        // 分页循环查询
        do {

            list($result, $msg, $total) = $this->getPullPageIndex($params, $shop_id, $pageIndex);

            if (!$result) {
                return [false, $msg];
            }

            $msgArr[] = sprintf('Page [%s]：%s', $pageIndex, $msg);

            if ($total <= $pageIndex * $pageSize) {
                break;
            }

            $pageIndex++;
            
        } while (true);

        
        return [true, implode('；', $msgArr)];
    }

    /**
     * 处理退供差异单
     * 
     * 
     * @param array $vreturndiff 退供差信息
     * @return array
     * */
    public function processVReturnDiff($vreturndiff, $shop_id)
    {

        $trx = kernel::database()->beginTransaction();
        try {
            $result = $this->_createVReturn($vreturndiff, $shop_id);

            kernel::database()->commit($trx);

            return $result;
        } catch (Exception $e) {

            kernel::database()->rollback();

            return [false, $e->getMessage()];
        }
    }

    /**
     * 获取退货差异明细
     * 
     * 
     * @param string $rv_difference_no 退供差单号
     * @param string $shop_id 店铺ID
     * @return array
     * */
    private function _getReturnDiffInterDetail($rv_difference_no,$return_no, $shop_id)
    {
        $params = [
            'rv_difference_no' => $rv_difference_no,
            'return_no' => $return_no,
        ];

        $pageIndex = 1;
        $pageSize  = 50;

        $items = [];

        do {
            $params['pageIndex'] = $pageIndex;
            $params['pageSize']   = $pageSize;

            $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getReturnDiffInterDetail($params);

            if ($rsp_data['rsp'] != 'succ') {
                return [false, $rsp_data['err_msg']];
           }

           $data = $rsp_data['data'];

           if (!$data['total']) {
               return [false, sprintf('[%s]查询明细不存在', $rv_difference_no)];
           }

           if (!$data['deatil_list']) {
                return [false, sprintf('[%s]查询第[%s]页明细为空', $rv_difference_no, $pageIndex)];
           }

           $items = array_merge($items, $data['deatil_list']);

           if ($data['total'] <= $pageIndex * $pageSize) {
               break;
           }

            $pageIndex++;
        } while (true);

        return [true, '获取成功', ['items' => $items]];
    }

    /**
     * 保存退供差异单
     * 
     * 
     * @param array $vreturndiff 退供差异单信息
     * @param string $shop_id 店铺ID
     * @return array
     * @throws \Exception
     * */
    private function _createVReturn($vreturndiff, $shop_id)
    {
        if (!$vreturndiff['rv_difference_no']) {
            throw new Exception('退供差异单号不能为空');
        }

        if (!$shop_id) {
            throw new Exception('店铺ID不能为空');
        }

        $data = [
            'rv_difference_no' => $vreturndiff['rv_difference_no'],
            'status_name' => $vreturndiff['status_name'],
            'sub_return_no' => $vreturndiff['sub_return_no'],
            'return_no' => $vreturndiff['return_no'],
            'po_no' => $vreturndiff['po_no'],
            'schedule_id' => $vreturndiff['schedule_id'],
            'poDelivery_type_name' => $vreturndiff['poDelivery_type_name'],
            'create_time_str' => $vreturndiff['create_time_str'],
            'update_time_str' => $vreturndiff['update_time_str'],
            'contractor' => $vreturndiff['contractor'],
            'telephone' => $vreturndiff['telephone'],
            'follow_up_department' => $vreturndiff['follow_up_department'],
            'follow_up_name' => $vreturndiff['follow_up_name'],
            'step_back_po' => $vreturndiff['step_back_po'],
            'ebs_bill_number' => $vreturndiff['ebs_bill_number'],
            'step_back_transport_no' => $vreturndiff['step_back_transport_no'],
            'required_transport_no' => $vreturndiff['required_transport_no'],
            'warehouse_name' => $vreturndiff['warehouse_name'],
            'return_type_name' => $vreturndiff['return_type_name'],
            'status' => $vreturndiff['status'],
            'status_note' => $vreturndiff['status_note'],
            'is_complete' => $vreturndiff['is_complete'],
            'approve_qty' => $vreturndiff['approve_qty'],
            'not_approve_qty' => $vreturndiff['not_approve_qty'],
            'shop_id' => $shop_id,
            'is_download_finished' => '1',
            'download_msg' => '',
        ];

        list($detailRs, $msg, $vrData) = $this->_getReturnDiffInterDetail($vreturndiff['rv_difference_no'], $vreturndiff['return_no'],$shop_id);

        if (!$detailRs) {
            $data['is_download_finished'] = 0;
            $data['download_msg'] = $msg;
        }

        if ($vrData && is_array($vrData['items'])) {
            $data['return_price_discount'] = array_sum(array_column($vrData['items'], 'return_price_discount'));
            $data['return_diff_amount'] = array_sum(array_column($vrData['items'], 'return_diff_amount'));
            $data['record_quantity'] = array_sum(array_column($vrData['items'], 'record_quantity'));
            $data['real_quantity'] = array_sum(array_column($vrData['items'], 'real_quantity'));
            $data['pay_quantity'] = array_sum(array_column($vrData['items'], 'pay_quantity'));
            $data['diff_amount'] = array_sum(array_column($vrData['items'], 'diff_amount'));
            $data['diff_quantity'] = array_sum(array_column($vrData['items'], 'diff_quantity'));
        }


        $vReturnDiffMdl = app::get('vop')->model('vreturn_diff');
        $vReturnDiffItemMdl = app::get('vop')->model('vreturn_diff_items');

        $oldVReturnDiff = $vReturnDiffMdl->db_dump([
            'rv_difference_no'  => $vreturndiff['rv_difference_no'],
            'shop_id'           => $shop_id,
        ], 'id');

        if ($oldVReturnDiff) {
            $id = $oldVReturnDiff['id'];

            $affect_rows = $vReturnDiffMdl->update($data, ['id' => $id]);

            if ($affect_rows != 1) {
                throw new Exception(sprintf('[%s]更新失败：%s', $data['rv_difference_no'], $vReturnDiffMdl->db->errorinfo()));
            }

        } else {

            $id = $vReturnDiffMdl->insert($data); 

            if (!$id) {
                throw new Exception(sprintf('[%s]保存失败：%s', $data['rv_difference_no'], $vReturnDiffMdl->db->errorinfo()));
            }
        }

        // 日志
        app::get('ome')->model('operation_log')->write_log('vreturn_diff@vop', $id, '创建退供差异单');

        // 明细处理
        if ($vrData && is_array($vrData['items'])){
            $tmpItems = $vReturnDiffItemMdl->getList('id,diff_id,item_sku,po_bn,box_id,anti_theft_code', [
                'diff_id' => $id,
            ]);

            $barcodeList = app::get('material')->model('codebase')->getList('bm_id,code', [
                'code' => array_column($vrData['items'], 'item_sku'),
                'type' => '1',
            ]);
            $barcodeMap = array_column($barcodeList, 'bm_id', 'code');

            $bmList = [];

            if ($barcodeMap) {
                $bmList = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', [
                    'bm_id' => $barcodeMap,
                ]);
                $bmList = array_column($bmList, null, 'bm_id');

            }



            $oldVReturnDiffItems = [];
            foreach ($tmpItems as $item) {
                $key = [$item['item_sku'],$item['po_bn'],$item['box_id'],$item['anti_theft_code']];
                $key = implode('_', $key);
                $oldVReturnDiffItems[$key] = $item;
            }
            
            foreach ($vrData['items'] as $item) {
                $key = [$item['item_sku'],$item['po_bn'],$item['box_id'],$item['anti_theft_code']];
                $key = implode('_', $key);
                $bm_id = $barcodeMap[$item['item_sku']] ?? '';
                if ($bm_id && isset($bmList[$bm_id])) {
                    $item['bm_id'] = $bm_id;
                    $item['material_bn'] = $bmList[$bm_id]['material_bn'];
                    $item['material_name'] = $bmList[$bm_id]['material_name'];
                }

                if ($oldVReturnDiffItems[$key]) {
                    $affect_rows = $vReturnDiffItemMdl->update($item, [
                        'id' => $item['id'],
                    ]);

                    if ($affect_rows != 1) {
                        throw new Exception(sprintf('[%s]更新明细[%s]失败：%s', $item['rv_difference_no'], $key, $vReturnDiffItemMdl->db->errorinfo()));
                    }
                } else {
                    $item['diff_id'] = $id;

                    if (!$vReturnDiffItemMdl->insert($item)) {

                        throw new Exception(sprintf('[%s]添加明细[%s]失败：%s', $item['rv_difference_no'], $key, $vReturnDiffItemMdl->db->errorinfo()));
                    }
                }
            }
        }

        // 生成销售单
        if ($vreturndiff['status'] == '11') {
            list($in_sale, $in_sale_errmsg) = $this->createSale($id);
            $in_sale = $in_sale ? '2' : '0';

            $vReturnDiffMdl->update([
                'in_sale' => $in_sale,
                'in_sale_errmsg' => $in_sale_errmsg,
            ], ['id' => $id]);
        }



        return [true, '创建退供差异单成功'];
    }

        /**
     * 创建Sale
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function createSale($id)
    {
        $diff = app::get('vop')->model('vreturn_diff')->db_dump($id);

        if ($diff['status'] != '11') {
            return [false, sprintf('[%s]状态不正确，非财务已记账创建销售单', $diff['rv_difference_no'])];
        }

        $saleMdl = app::get('billcenter')->model('sales');

        // 判断结算金额是否存在
        if ($saleMdl->db_dump(['original_bn' => $diff['rv_difference_no'], 'bill_type' => 'return_diff'])) {
            return [true, sprintf('[%s]已创建销售单', $diff['rv_difference_no'])];
        }

        $items = app::get('vop')->model('vreturn_diff_items')->getList('*', [
            'diff_id' => $id,
        ]);
        if (!$items) {
            return [false, sprintf('[%s]无明细，无法创建销售单', $diff['rv_difference_no'])];
        }
        $shop = app::get('ome')->model('shop')->db_dump(['shop_id' => $diff['shop_id']], 'shop_id,shop_bn,name');
    
        $bmIds           = array_unique(array_column($items, 'bm_id'));
        $materialExtList = app::get('material')->model('basic_material_ext')->getList('bm_id,retail_price', ['bm_id' => $bmIds]);
        $materialExtList = array_column($materialExtList, null, 'bm_id');
        
        $data = [
            'order_bn' => $diff['ebs_bill_number'],
            'bill_bn' => $diff['ebs_bill_number'],
            'bill_type' => 'RETURN_DIFF',
            'bill_id' => $diff['id'],
            'shop_id' => $diff['shop_id'],
            'shop_bn' => $shop['shop_bn'],
            'shop_name' => $shop['name'],
            'sale_time' => strtotime($diff['update_time_str']),
            'original_bn' => $diff['rv_difference_no'],
            'original_id' => $diff['id'],
            'total_amount' => 0,
            'total_sale_price' => 0,
            'total_settlement_amount' => 0,
        ];

        foreach ($items as $value) {
            $item = [];
            $retail_price              = $materialExtList[$item['bm_id']]['retail_price'] ?? 0;
            $amount                    = $retail_price * $item['nums'];
    
            $diff_amount = abs($value['diff_amount']);
            $item['material_bn'] = $value['material_bn'];
            $item['barcode'] = $value['item_sku'];
            $item['material_name'] = $value['material_name'];
            $item['bm_id'] = $value['bm_id'];
            $item['nums'] = abs($value['pay_quantity']);
            $item['price'] = abs($retail_price);
            $item['amount'] = abs($amount);
            $item['settlement_amount'] = abs($value['settlement_amount']);
            $item['sale_price'] = $diff_amount;
    
            $data['total_amount']      += $amount;
            $data['total_sale_price']  += $item['sale_price'];
            $data['settlement_amount'] += $item['settlement_amount'];

            $data['items'][] = $item;
        }

        return $saleMdl->create_sales($data);
    }
}