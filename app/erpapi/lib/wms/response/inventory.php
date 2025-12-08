<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_inventory extends erpapi_wms_response_abstract
{
    /**
     * wms.inventory.add
     *
     **/
    public function add($params){
        // 参数校验
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '盘点' . $params['inventory_bn']; 
        $this->__apilog['original_bn'] = $params['inventory_bn'];

        $data = array(
            'inventory_bn' => trim($params['inventory_bn']),
            'branch_bn'    => $params['warehouse'],
            'memo'         => $params['remark'],
            'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
            'wms_id'       => $this->__channelObj->wms['channel_id'],
            'autoconfirm'  => $params['autoconfirm'] == 'Y' ? 'Y' : 'N',
        );
        if('true' == app::get('ome')->getConf('wms.stock.inventory.finish.auto')) {
            $data['autoconfirm'] = 'Y';
        }
        $bd = kernel::single('console_receipt_stockchange')->getBranchId($this->__channelObj->wms['node_id'], $params['warehouse']);
        $data['negative_cc_branch_id'] = $bd['negative_cc_branch_id'];
        $data['negative_branch_id'] = $bd['negative_branch_id'];
        $inventoryMode = app::get('ome')->getConf('taoguaninventory.quantity.mode');
        $data['mode'] = $params['mode'] ? : ($inventoryMode  == '1' ? '1' : '2'); #1: 全量, 2: 增量
        $inventory_items = array();
        $items = $params['item'] ? json_decode($params['item'],true) : array();
        if ($items) {
            $product_bn = array_column($items, 'product_bn');
            $bc = app::get('material')->model('basic_material')->getList('bm_id,material_bn', ['material_bn'=>$product_bn]);
            $bc = array_column($bc, null, 'material_bn');
            foreach ($items as $key => $val) {
                if (!$val['product_bn']) continue;
                if(!$bc[$val['product_bn']]) continue;
                $bm_id = $bc[$val['product_bn']]['bm_id'];
                $inventory_items[$bm_id]['bm_id'] = $bm_id;
                $inventory_items[$bm_id]['material_bn'] = $val['product_bn'];
                if($val['normal_num'] || (!$val['normal_num'] && !$val['defective_num'] && isset($val['normal_num']))) {
                    if(empty($data['negative_branch_id'])) {
                        $this->__apilog['result']['msg'] = '缺少对应良品仓库：'.$data['branch_bn'];
                        return false;
                    }
                    $inventory_items[$bm_id]['zp']['diff_stores'] += (int)$val['normal_num'];
                    $inventory_items[$bm_id]['zp']['wms_stores'] += (int)$val['totalQty'];
                }
                if($val['defective_num'] || (!$val['normal_num'] && !$val['defective_num'] && !isset($val['normal_num']))) {
                    if(empty($data['negative_cc_branch_id'])) {
                        $this->__apilog['result']['msg'] = '缺少对应残品仓库：'.$data['branch_bn'];
                        return false;
                    }
                    $inventory_items[$bm_id]['cc']['diff_stores'] += (int)$val['defective_num'];
                    $inventory_items[$bm_id]['cc']['wms_stores'] += $val['totalQty'];
                }

                if($val['batch_code']){

                    $inventory_items[$bm_id]['batch'][] = array(
                        'purchase_code'     => $val['batch_code'],
                        'produce_code'      => $val['produceCode'],
                        'product_time'      => strtotime($val['productDate']) ? strtotime($val['productDate']) : 0,
                        'expire_time'       => strtotime($val['expireDate']) ? strtotime($val['expireDate']) : 0,
                        'normal_defective'  => ($val['inventoryType'] == 'CC' ? 'defective' : 'normal'),
                        'num'               => $val['inventoryType'] == 'CC' ? abs($val['defective_num']) : abs($val['normal_num']),
                    );
                }
            }
        }
        $data['items'] = $inventory_items;
        return $data;
    }

}
