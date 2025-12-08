<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 转储单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_transferorder extends erpapi_wms_response_abstract
{    
    /**
     * wms.transferorder.update
     *
     **/
    public function update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'转储单'.$params['order_code'];
        $this->__apilog['original_bn'] = $params['order_code'];
        $this->_dealWMSParams($params);

        $data = array(
            'stockdump_bn' => $params['order_code'],
            'branch_bn'    => '',
            'status'              => $params['order_status'] ?: $params['io_status'],
            'memo'         => '',
            'operate_time' =>isset($params['create_time']) ? $params['create_time'] : date('Y-d-m H:i:s'),
            'wms_id'       => $this->__channelObj->wms['channel_id'],
            'from_warehouse_code' => $params['from_warehouse_code'],
            'to_warehouse_code'   => $params['to_warehouse_code'],
            'erp_stockdump_bn'    => $params['erp_order_code'],
        );


        $stockdump_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();
        
        if($items){
            // 多行合并
            $newItems = [];

            foreach ($items as $val) {
                $bn = $val['product_bn'];

                if (!$bn) {
                    continue;
                }

                $newItems[$bn]['product_bn'] = $bn;
                $newItems[$bn]['order_code'] = $val['order_code'];
                $newItems[$bn]['item_id']    = $val['item_id'];

                $newItems[$bn]['plan_count'] += $val['plan_count'];
                $newItems[$bn]['in_count'] += $val['in_count'];
                $newItems[$bn]['normal_num'] += $val['normal_num'];
                $newItems[$bn]['defective_num'] += $val['defective_num'];
            }

            unset($items);
            foreach ($newItems as $key => $val) {
                if(!$val['product_bn'])  continue;

                $stockdump_items[] = array(
                    'bn' => $val['product_bn'],
                    'num'=> $val['normal_num'],
                    'appro_price' => 0,
                );
            }
        }

        $data['items'] = $stockdump_items;
        return $data;
    }

    protected function _dealWMSParams($params)
    {
        if ((empty($params['order_code']) && empty($params['erp_order_code']))) {
            return [false, ['msg' => '参数不符合规范']];
        }
        $nodeId    = $this->__channelObj->channel['node_id'];
        $wstMdl    = app::get('console')->model('wms_transferorder');
        $wmsStatus = $params['order_status'];
        if ($params['erp_order_code']) {
            if ($row = $wstMdl->db_dump(['erp_order_code' => $params['erp_order_code'], 'wms_node_id' => $nodeId], 'id,wms_status')) {
                if ($row['wms_status'] != $wmsStatus) {
                    $wstMdl->update(['wms_status' => $wmsStatus], ['id' => $row['id']]);
                    app::get('ome')->model('operation_log')->write_log('wms_transferorder@console', $row['id'], '更新成功：' . $wmsStatus);
                }
                return [true, ['msg' => '已经存在']];
            }
        } else {
            if ($row = $wstMdl->db_dump(['order_code' => $params['order_code']], 'id, wms_status')) {
                if ($row['wms_status'] != $wmsStatus) {
                    $wstMdl->update(['wms_status' => $wmsStatus], ['id' => $row['id']]);
                    app::get('ome')->model('operation_log')->write_log('wms_transferorder@console', $row['id'], '更新成功：' . $wmsStatus);
                }
                return [true, ['msg' => '已经存在']];
            }
        }
        $inData                        = [];
        $inData['order_code']          = $params['order_code'];
        $inData['erp_order_code']      = $params['erp_order_code'];
        $inData['wms_status']          = $wmsStatus;
        $inData['wms_node_id']         = $nodeId;
        $inData['out_order_code']      = $params['out_order_code'];
        $inData['in_order_code']       = $params['in_order_code'];
        $inData['out_confirm_time']    = $params['out_confirm_time'];
        $inData['in_confirm_time']     = $params['in_confirm_time'];
        $inData['create_time']         = $params['create_time'];
        $inData['from_warehouse_code'] = $params['from_warehouse_code'];
        $inData['to_warehouse_code']   = $params['to_warehouse_code'];
        $inData['owner_code']          = $params['owner_code'];
        $inData['extend_props']        = $params['extendProps'];
        $id                            = $wstMdl->insert($inData);
        if (!$id) {
            return [false, ['msg' => '主表写入失败']];
        }
        $items = isset($params['item']) ? json_decode($params['item'], true) : array();
        if ($items) {
            $inItems = [];
            foreach ($items as $key => $val) {
                $inItems[] = [
                    'wst_id'        => $id,
                    'product_bn'    => $val['product_bn'],
                    'normal_num'    => $val['normal_num'],
                    'defective_num' => $val['defective_num'],
                    'wms_item_id'   => $val['item_id'],
                    'in_count'      => $val['in_count'],
                    'plan_count'    => $val['plan_count'],
                ];
            }
            $wriMdl = app::get('console')->model('wms_transferorder_items');
            $sql    = kernel::single('ome_func')->get_insert_sql($wriMdl, $inItems);
            $wriMdl->db->exec($sql);
        }
        app::get('ome')->model('operation_log')->write_log('wms_transferorder@console', $id, '接收成功：' . $wmsStatus);
        return [true, ['msg' => '处理完成']];
    }
}
