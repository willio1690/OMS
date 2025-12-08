<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_purchase_pick
{
    /**
     * 下载拣货单列表
     * @param string $po_nos 采购单号
     * @param string $shop_id
     * @param string $pick_no 拣货单号
     * @return array
     */
    public function getPullList($po_nos, $shop_id, $pick_no = '')
    {
        $params    = [];

        if ($po_nos) {
            $params['po_no'] = is_array($po_nos) ? implode(',', $po_nos) : $po_nos;
        }
        
        if ($pick_no) {
            $params['pick_no'] = $pick_no;
        }

        if (!$params) {
            return [false, '参数错误'];
        }

        $rsp_data  = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getPickList($params);

        if ($rsp_data['rsp'] != 'succ') {
            return [false, $rsp_data['err_msg']];
        }

        if (!$rsp_data['po_list']) {
            return [false, '未获取到拣货单'];
        }

        $pickBillList = app::get('purchase')->model('pick_bills')->getList('pick_no,is_download_finished', [
            'pick_no' => array_column($rsp_data['po_list'], 'pick_no'),
        ]);
        $pickBillList = array_column($pickBillList, 'is_download_finished', 'pick_no');

        foreach ($rsp_data['po_list'] as $pi_key => $pi_val)
        {
            // 已经同步完成的拣货单不再更新
            if ($pickBillList[$pi_val['pick_no']] && $pickBillList[$pi_val['pick_no']] == '1') {
                continue;
            }

            $this->processPick($pi_val, $shop_id);
        }

        return [true];
    }

    /**
     * 处理拣货单
     *
     * @param array $pickInfo 拉取的单个拣货单信息
     * @param string $shop_id 店铺ID
     **/
    public function processPick($pickInfo, $shop_id){
        $trx = kernel::database()->beginTransaction();

        try {
            // 保存拣货单及拣货单明细
            list($result, $msg) = $this->_pullPickDetail($pickInfo, $shop_id);

            kernel::database()->commit($trx);
        } catch (\Throwable $th) {
            kernel::database()->rollback();

            return [false, $th->getMessage()];
        }


        return [$result, $msg];
    }

    /**
     * 拉取拣货单明细
     * 
     * @param array $pickInfo 拉取的单个拣货单信息
     * @param string $shop_id 店铺ID
     * 
     * @return bool
     */
    private function _pullPickDetail($pickInfo, $shop_id) {
        $page           = 1;
        $limit          = 50;
        $product_list   = [];

        do {
            
            // 组织数据
            $params = [
                'po_no'     => $pickInfo['po_no'],
                'pick_no'   => $pickInfo['pick_no'],
                'page'      => $page,
                'limit'     => $limit,
            ];

            $rsp_detail  = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getPickDetail($params);

            // 组织拣货单信息
            if($rsp_detail['rsp'] == 'succ' && $rsp_detail['pick_product_list'])
            {
                
                $barcodes = array_column($rsp_detail['pick_product_list'], 'barcode');
                
                // 获取供货价 start ++++++++++
                list($skuPriceRs,,$skuPriceData) = kernel::single('purchase_purchase_sku')->getSkuPriceInfo($shop_id, $pickInfo['po_no'],$barcodes);

                if ($skuPriceRs) {
                    $skuPriceData = array_column($skuPriceData, null, 'barcode');

                    foreach ($rsp_detail['pick_product_list'] as $ppKey => $ppVal) {
                        $spd = $skuPriceData[$ppVal['barcode']];

                        // 不含税 -> 改为原价
                        $rsp_detail['pick_product_list'][$ppKey]['price'] = round($spd['price'],3);

                        // 含税
                        $rsp_detail['pick_product_list'][$ppKey]['market_price'] = $spd['actual_market_price'];
                    }
                }
                // 获取供货价 end ++++++++++
                

                $product_list    = array_merge($product_list, $rsp_detail['pick_product_list']);
            }

            if (count($product_list) >= $rsp_detail['total']) {
                break;
            }

            $page++;
        } while (true);
        
        
        if (!$product_list) {
            throw new Exception(sprintf('[%s]未获取到拣货单明细',$pickInfo['pick_no']));
        }

        $pickInfo['is_download_finished'] = count($product_list) >= $rsp_detail['total'] ? '1' : '0';
        $pickInfo['product_list'] = $product_list;

        return $this->savePick($pickInfo, $shop_id);
    }

    /**
     * 保存拣货单
     *
     * @param array $pickInfo 拣货单信息
     * @return array
     **/
    public function savePick($pickInfo, $shop_id)
    {

        $poMdl              = app::get('purchase')->model('order');
        $pickItemMdl        = app::get('purchase')->model('pick_bill_items');
        $pickMdl            = app::get('purchase')->model('pick_bills');
        $pickCheckItemMdl   = app::get('purchase')->model('pick_bill_check_items');

        if (!$pickInfo['po_no']) {
            return [false, '未获取到po单号'];
        }

        $po = $poMdl->db_dump(['po_bn' => $pickInfo['po_no']], 'po_id,po_bn');

        // 创建信息
        $data    = [
            'pick_no'       => $pickInfo['pick_no'],//拣货单编号
            'po_id'         => $po['po_id'],//采购单ID
            'po_bn'         => $po['po_bn'],//po单号
            'to_branch_bn'  => $pickInfo['sell_site'],//入库仓编码
            'order_cate'    => $pickInfo['order_cate'],//订单类别
            'pick_num'      => intval($pickInfo['pick_num']),//拣货数量
            'delivery_status'   => intval($pickInfo['delivery_status'] ?: '1'),//平台送货状态
            'delivery_num'  => intval($pickInfo['delivery_num']),//平台发货数量
            'shop_id'       => $shop_id,
            'is_download_finished' => $pickInfo['is_download_finished'],
            'last_modified' => time(),
        ];


        $pp = $pickMdl->db_dump(['pick_no' => $data['pick_no']], 'bill_id,status');
        if ($pp) {
            // 如果已经审核，则不允许修改
            if ($pp['status'] != '1'){
                throw new Exception(sprintf('[%s]拣货单已经审核，不允许修改！', $data['pick_no']));
            }

            $bill_id = $pp['bill_id'];

            $affect_rows = $pickMdl->update($data, [
                'bill_id' => $bill_id,
                'status' => '1',
            ]);
            if ($affect_rows != 1) {
                throw new Exception(sprintf('[%s]拣货单更新失败%s：'.$pickMdl->db->errorinfo(), $data['pick_no'],$affect_rows?:''));
            }
        } else {
            $data['create_time'] = time();

            $bill_id = $pickMdl->insert($data);

            if (!$bill_id) {
                throw new Exception(sprintf('[%s]拣货单创建失败：'.$pickMdl->db->errorinfo(), $data['pick_no']));
            }
        }

        app::get('ome')->model('operation_log')->write_log('create_vopick@ome', $bill_id, '拣货单创建成功');
        
        $barcodes = array_column($pickInfo['product_list'], 'barcode');
        $barcodeList = app::get('material')->model('codebase')->getList('bm_id, code', [
            'code' => $barcodes,
            'type' => '1',
        ]);
        $barcodeList = array_column($barcodeList, 'bm_id', 'code');

        $ppItems = $pickItemMdl->getList('bill_item_id,bill_id,bn,barcode,num', [
            'bill_id' => $bill_id,
            'barcode' => $barcodes,
        ]);
        $ppItems = array_column($ppItems, null, 'barcode');

        $batchList = [];
        //拣货单明细
        foreach ($pickInfo['product_list'] as $p_item)
        {
            $p_item['product_name']    = str_replace(array("\t","\r","\n"), '', $p_item['product_name']);
            
            $item    = [
                'product_name'      => $p_item['product_name'],//商品名称
                'bn'                => $p_item['art_no'],//货号
                'product_id'        => $barcodeList[$p_item['barcode']],
                'barcode'           => $p_item['barcode'],//商品条码
                'size'              => $p_item['size'],//尺寸
                'num'               => intval($p_item['stock']),//拣货数量
                'not_delivery_num'  => intval($p_item['not_delivery_num']),//未送货数量
                'quality_check_flag'  => $p_item['quality_check_flag'],
                'goods_type_map'      => json_encode($p_item['goods_type_map'], JSON_UNESCAPED_UNICODE),
                'bill_id'             => $bill_id,
                'security_type'     => $p_item['security_type'],
                'price'             => $p_item['price'],
                'market_price'      => $p_item['market_price'],
            ];

            $needUnfreeze = false;
            $isNewItem = true;
            $bill_item_id = $ppItems[$p_item['barcode']]['bill_item_id'];
            if ($bill_item_id){
                $affect_rows = $pickItemMdl->update($item, ['bill_item_id' => $bill_item_id]);

                if (!$affect_rows) {
                    throw new \Exception(sprintf('[%s]拣货单明细[%s]更新失败：'.$pickItemMdl->db->errorinfo(), $data['pick_no'], $p_item['barcode']));
                }
                $isNewItem = false;
                $oldNum = $ppItems[$p_item['barcode']]['num'];
                if ($oldNum != $item['num']) {
                    $needUnfreeze = true;
                }
            } else {
                $bill_item_id = $pickItemMdl->insert($item);

                if (!$bill_item_id) {
                    throw new \Exception(sprintf('[%s]拣货单明细[%s]创建失败：'.$pickItemMdl->db->errorinfo(), $data['pick_no'], $p_item['barcode']));
                }

            }

            // 如果商品不存在
            if (!$item['product_id']) {
                $pickMdl->update([
                    'is_download_finished' => '0',
                    'download_msg'=>sprintf('货号[%s]不存在', $item['bn'])
                ], [
                    'bill_id' => $bill_id,
                ]);
            } else {

                if ($needUnfreeze) {
                    // 更新拣货单明细的冻结，先释放旧的，再冻结新的
                    $oldNum = $ppItems[$p_item['barcode']]['num'];

                    $freezeData = [];
                    $freezeData['bm_id']    = $item['product_id'];
                    $freezeData['sm_id']    = 0; // 唯品会拣货单明细是用barcode映射bm_id，所以没有sm_id
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                    $freezeData['bill_type']= material_basic_material_stock_freeze::__VOPICKBILLS;
                    $freezeData['obj_id']   = $bill_id;
                    $freezeData['branch_id']= 0;
                    $freezeData['bmsq_id']  = material_basic_material_stock_freeze::__SHARE_STORE;
                    $freezeData['num']      = $oldNum;
                    $freezeData['bm_bn']    = $item['bn'];

                    $batchList['-'][] = $freezeData;
                }
                if ($isNewItem || $needUnfreeze) {
                    $freezeData = [];
                    $freezeData['bm_id']    = $item['product_id'];
                    $freezeData['sm_id']    = 0; // 唯品会拣货单明细是用barcode映射bm_id，所以没有sm_id
                    $freezeData['bn']       = $item['bn'];
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                    $freezeData['bill_type']= material_basic_material_stock_freeze::__VOPICKBILLS;
                    $freezeData['obj_id']   = $bill_id;
                    $freezeData['shop_id']  = $shop_id;
                    $freezeData['branch_id']= 0;
                    $freezeData['bmsq_id']  = material_basic_material_stock_freeze::__SHARE_STORE;
                    $freezeData['num']      = $item['num'];
                    $freezeData['obj_bn']   = $data['pick_no'];

                    $batchList['+'][] = $freezeData;
                }
            }

            // 换货质检项目
            if ($p_item['quality_check_flag'] == '1' && $p_item['check_items']) {

                kernel::single('ome_bill_label')->markBillLabel($bill_item_id, '', 'quality_check', 'pick_bill_item', $err);
                
                // 删除明细重新保存
                $pickCheckItemMdl->delete([
                    'bill_id' => $bill_id,
                    'bill_item_id' => $bill_item_id,
                ]);

                foreach ($p_item['check_items'] as $ci) {
                    $checkItems = [
                        'bill_id'               => $bill_id,
                        'bill_item_id'          => $bill_item_id,
                        'bn'                    => $item['bn'],
                        'barcode'               => $item['barcode'],
                        'channel'               => $ci['channel'],
                        'problem_desc'          => $ci['problem_desc'],
                        'order_label'           => $ci['order_label'],
                        'image_fileid_list'     => $ci['image_list'] ? json_encode($ci['image_list']) : '',
                        'video_fileid_list'     => $ci['video_list'] ? json_encode($ci['video_list']) : '',
                        'delivery_warehouse'    => $ci['delivery_warehouse'],
                        'order_sn'              => $ci['order_sn'],
                        'first_classification'  => $ci['first_classification'],
                        'second_classification' => $ci['second_classification'],
                        'third_classification'  => $ci['third_classification'],
                    ];

                    if (!$pickCheckItemMdl->insert($checkItems)){
                        throw new \Exception(sprintf('[%s]换货质检明细[%s]创建失败：'.$pickCheckItemMdl->db->errorinfo(), $data['pick_no'], $p_item['barcode']));
                    }
                }
            }
            // 是否有优先发货的标识
            if ($item['priorityDelivery'] == '1') {
                kernel::single('ome_bill_label')->markBillLabel($bill_item_id, '', 'priority_delivery', 'pick_bill_item', $err);
            }

            
            // 是否有重点检查的标识
            if ($p_item['quality_check_flag'] == '1') {
                $quality_check_flag = '1';
            }

            // 是否有优先发货的标识
            if (is_array($p_item['goods_type_map']) && $p_item['goods_type_map']['priorityDelivery'] == '1') {
                $priorityDelivery = '1';
            }
        }

        // 生成预占库存流水
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $rs = $basicMStockFreezeLib->unfreezeBatch($batchList['-'], __CLASS__.'::'.__FUNCTION__, $err);
        if (!$rs) {
            throw new \Exception('拣货单预占库存更新失败：'.$err);
        }
        $rs = $basicMStockFreezeLib->freezeBatch($batchList['+'], __CLASS__.'::'.__FUNCTION__, $err);
        if (!$rs) {
            throw new \Exception('拣货单预占库存创建失败：'.$err);
        }

        // 是否换货质检打标
        if ($quality_check_flag) {
            kernel::single('ome_bill_label')->markBillLabel($bill_id, '', 'quality_check', 'pick_bill', $err);
        }

        // 是否优先发货打标
        if ($priorityDelivery) {
            kernel::single('ome_bill_label')->markBillLabel($bill_id, '', 'priority_delivery', 'pick_bill', $err);
        }
        
        return [true];
    }

    /**
     * 创建拣货单
     *
     *
     * @param string $po_no 采购单号
     * @param string $shop_id 店铺ID
     * @return array
     **/
    public function createPick($po_no, $shop_id)
    {
        if (!$po_no) {
            return [false, '参数错误：po_no不能为空'];
        }

        $params  = [
            'po_no' => $po_no,
        ];

        $rsp    = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_createPick($params);

        if ($rsp['rsp'] != 'succ'){
            return [false, '创建拣货单失败：'.$rsp['msg']];
        }

        if (!$rsp['pick_info']){
            return [false, '拣货单号不存在'];
        }

        return $this->getPullList($po_no, $shop_id);
    }
    
}
