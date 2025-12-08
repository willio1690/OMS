<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/11/28 16:42:33
 * @describe: 加工单
 * ============================
 */

class erpapi_wms_response_storeprocess extends erpapi_wms_response_abstract
{    
    /**
     * wms.storeprocess.status_update
     *
     **/
    public function status_update($params){
        $this->__apilog['title']       = '加工单确认';
        $this->__apilog['original_bn'] = $params['processOrderCode'];
        $this->_dealWMSParams($params);
        $data = [
            'mp_bn' => $params['processOrderCode'],
            'out_mp_bn' => $params['processOrderId'],
            'complete_time' => $params['orderCompleteTime'],
        ];
        $items = isset($params['productitems']) ? json_decode($params['productitems'],true) : array();
        if($items){
            $inItems = [];
            foreach($this->transfer_items($items) as $key=>$val){

                
                if($inItems[$val['itemCode']]) {
                    $inItems[$val['itemCode']]['number'] += $val['quantity'];
                } else {
                    $inItems[$val['itemCode']] = [
                        'bm_bn' => $val['itemCode'],
                        'number' => $val['quantity'],
                    ];
                }

                if($val['batchCode']){
                    $inItems[$val['itemCode']]['batch'][] = array(
                        'bn'                => $val['itemCode'],
                        'purchase_code'     => $val['batchCode'],
                        'produce_code'      => $val['produceCode'],
                        'product_time'      => strtotime($val['productDate']) ? strtotime($val['productDate']) : 0,
                        'expire_time'       => strtotime($val['expireDate']) ? strtotime($val['expireDate']) : 0,
                        'normal_defective'  => ($val['inventoryType'] == 'CC' ? 'defective' : 'normal'),
                        'num'               => $val['quantity'],
                    );
                }
            }
        }
        $data['material_items'] = $inItems;
        $items = isset($params['materialitems']) ? json_decode($params['materialitems'],true) : array();
        if($items){
            $outItems = [];
            foreach($this->transfer_items($items) as $key=>$val){


                if($outItems[$val['itemCode']]) {
                    $outItems[$val['itemCode']]['number'] += $val['quantity'];
                } else {
                    $outItems[$val['itemCode']] = [
                        'bm_bn' => $val['itemCode'],
                        'number' => $val['quantity'],
                    ];
                }

                if($val['batchCode']){
                    $outItems[$val['itemCode']]['batch'][] = [
                        'bn'                => $val['itemCode'],
                        'purchase_code'     => $val['batchCode'],
                        'produce_code'      => $val['produceCode'],
                        'product_time'      => strtotime($val['productDate']) ? strtotime($val['productDate']) : 0,
                        'expire_time'       => strtotime($val['expireDate']) ? strtotime($val['expireDate']) : 0,
                        'normal_defective'  => ($val['inventoryType'] == 'CC' ? 'defective' : 'normal'),
                        'num'               => $val['quantity'],
                    ];
                }
            }
        }
        $data['product_items'] = $outItems;
        return $data;
    }

    protected function transfer_items($items) {
        return isset($items['item'][0]) ? $items['item'] : [$items['item']];
    }

    protected function _dealWMSParams($params) {
        if((empty($params['processOrderId']) && empty($params['processOrderCode']))) {
            return [false, ['msg'=>'参数不符合规范']];
        }
        $nodeId = $this->__channelObj->channel['node_id'];
        $wstMdl = app::get('console')->model('wms_storeprocess');
        if($params['processOrderCode']) {
            if($row = $wstMdl->db_dump(['mp_bn'=>$params['processOrderCode'], 'wms_node_id'=>$nodeId], 'id')) {
                return [true, ['msg'=>'已经存在']];
            }
        } else {
            if($row = $wstMdl->db_dump(['processOrderId'=>$params['processOrderId']], 'id')) {
                return [true, ['msg'=>'已经存在']];
            }
        }
        $inData = [];
        $inData['process_order_id'] = $params['processOrderId'];
        $inData['mp_bn'] = $params['processOrderCode'];
        $inData['wms_node_id'] = $nodeId;  
        $inData['order_type'] = $params['orderType'];
        $inData['order_complete_time'] = $params['orderCompleteTime'];
        $inData['actual_qty'] = $params['actualQty'];
        $inData['remark'] = $params['remark'];
        $inData['warehouse_code'] = $params['warehouseCode'];
        $inData['extend_props'] = $params['extendProps'];
        $id = $wstMdl->insert($inData);
        if(!$id) {
            return [false, ['msg'=>'主表写入失败']];
        }
        $items = isset($params['materialitems']) ? json_decode($params['materialitems'],true) : array();
        if($items){
            $inItems = [];
            foreach($this->transfer_items($items) as $key=>$val){
                $inItems[] = [
                    'wsp_id' => $id,
                    'item_code' => $val['itemCode'],
                    'item_id' => $val['itemId'],
                    'inventory_type' => $val['inventoryType'],
                    'quantity' => $val['quantity'],
                    'product_date' => $val['productDate'],
                    'expire_date' => $val['expireDate'],
                    'produce_code' => $val['produceCode'],
                    'batch_code' => $val['batchCode'],
                    'remark' => $val['remark'],
                ];
            }
            $wriMdl = app::get('console')->model('wms_storeprocess_materialitems');
            $sql = kernel::single('ome_func')->get_insert_sql($wriMdl, $inItems);
            $wriMdl->db->exec($sql);
        }
        $items = isset($params['productitems']) ? json_decode($params['productitems'],true) : array();
        if($items){
            $outItems = [];
            foreach($this->transfer_items($items) as $key=>$val){
                $outItems[] = [
                    'wsp_id' => $id,
                    'item_code' => $val['itemCode'],
                    'item_id' => $val['itemId'],
                    'inventory_type' => $val['inventoryType'],
                    'quantity' => $val['quantity'],
                    'product_date' => $val['productDate'],
                    'expire_date' => $val['expireDate'],
                    'produce_code' => $val['produceCode'],
                    'batch_code' => $val['batchCode'],
                    'remark' => $val['remark'],
                ];
            }
            $wriMdl = app::get('console')->model('wms_storeprocess_productitems');
            $sql = kernel::single('ome_func')->get_insert_sql($wriMdl, $outItems);
            $wriMdl->db->exec($sql);
        }
        app::get('ome')->model('operation_log')->write_log('wms_storeprocess@console',$id, '接收成功');
        return [true, ['msg'=>'处理完成']];
    }
}
