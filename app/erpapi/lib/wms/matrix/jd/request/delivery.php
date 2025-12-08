<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author
 * @describe 京东发货单
 */
class erpapi_wms_matrix_jd_request_delivery extends erpapi_wms_request_delivery
{
    protected $outSysProductField = 'item_code';

    protected function _getNextObjType()
    {
        return 'search_delivery';
    }

    protected function _needEncryptOriginData($sdf) {
        if ($sdf['shop_type'] == '360buy') {
            return true;;
        }
        return parent::_needEncryptOriginData($sdf);
    }

    /**
     * 创建
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function create($sdf){
        foreach ($sdf as $dk => $dv) {
            if(in_array($sdf['shop_type'], array('360buy'))) {
                $order = reset($sdf['order_info']);
                $encrypt_source_data = $order['encrypt_source_data'];
                if($encrypt_source_data['oaid']){
                    $sdf['oaid'] = $encrypt_source_data['oaid'];
                    
                    if(is_string($dv) && $index = strpos($dv , '>>')) {
                        $sdf[$dk] = substr($dv , 0, $index);
                    }
                }
            }
        }
        
        return parent::create($sdf);
    }

    protected function _format_delivery_create_params($sdf)
    {
        $params                  = parent::_format_delivery_create_params($sdf);
        $isv_source              = app::get('wmsmgr')->getConf('isv_source_' . $this->__channelObj->wms['channel_id']);
        $params['isv_source']    = $isv_source ? $isv_source : 'shopex';
        $params['order_type']    = 'OUT_SALE';
        $params['receiver_time'] = strtotime($params['receiver_time']) ? date('Y-m-d H:i:s', strtotime($params['receiver_time'])) : '';
        $params['is_protect']    = $params['is_protect'] == 'true' ? true : false;
        $params['department_no'] = app::get('wmsmgr')->getConf('department_no_' . $this->__channelObj->wms['channel_id']);
        //$params['receiver_address'] = $sdf['ship_province'].$sdf['ship_city'].$sdf['ship_district'].$sdf['ship_addr'];

        // 当物流类型为沧海物流时-查询erp内部货品编码
        if ($this->__channelObj->wms['addon']['wms_type'] == '1') {
            $params['order_source'] = 'OTHER';
        }

        if ($params['order_source'] == '360BUY' && $sdf['source'] == 'matrix') {
            $params['order_source'] = 'JD';
        } else {
            $params['order_source'] = 'OTHER';
        }

        $custom_field = [];
        
        if ($sdf['oaid']){
            $custom_field['OAID'] = $sdf['oaid'];
        }
        
        $params['custom_field'] = json_encode($custom_field);

        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {

        $params = array(
            'warehouse_code' => $sdf['branch_bn'],
            'out_order_code' => $sdf['original_delivery_bn'],
        );

        return $params;

    }

    /**
     * @param $rs
     * @return array
     * currentStatus :  10014, 已下发库房
    10010, 订单初始化
    10015, 任务已分配
    10016, 拣货下架
    10017, 复核
    10018, 货品已打包
    10019, 交接发货
    10028, 取消成功
     */
    protected function _deal_search_result($rs)
    {

        $resultData = array();
        $data       = json_decode($rs['data'], true);
        // 京东沧海
        if ($data) {
            // 定义临时变量
            $items     = array();
            $outer_sku = array();
            $sku_list  = array();
            $waybill   = array();
            $itemList  = json_decode($data['item'], true);
            // 物流单号
            foreach ($data['orderPackageList'] as $val) {
                $waybill[] = $val['packageNo'];
            }
            // 商品处理
            foreach ($itemList as $val) {
                $outer_sku[]                  = $val['product_bn'];
                $sku_list[$val['product_bn']] = array(
                    'product_bn'    => $val['product_bn'],
                    'num'           => $val['normal_num'],
                    'normal_num'    => $val['normal_num'],
                    'defective_num' => $val['defective_num'],
                );
            }
            // 当物流类型为沧海物流时-查询erp内部货品编码
            //if($this->__channelObj->channel['addon']['wms_type'] == '1'){
            $res = app::get('console')->model('foreign_sku')->getList('inner_sku,outer_sku', array('outer_sku' => $outer_sku));
            if ($res) {
                foreach ($res as $val) {
                    if ($sku_list[$val['outer_sku']]) {
                        $sku_list[$val['outer_sku']]['product_bn'] = $val['inner_sku'];
                    } else {
                        $sku_list[$val['outer_sku']]['product_bn'] = '';
                    }
                }
            }
            //}
            // 进行数据处理
            $items = array_values($sku_list);
            if ($data['status'] == 'FINISH') {
                $data['status'] = 'DELIVERY';
            }

            $resultData['type']         = $data['type'];
            $resultData['status']       = $data['status'];
            $resultData['remark']       = $data['remark'];
            $resultData['delivery_bn']  = $data['stock_bn'];
            $resultData['operate_time'] = $data['operate_time'];
            $resultData['logi_no']      = $data['logi_no'];
            $resultData['logistics']    = $data['logistics'];
            $resultData['warehouse']    = $data['warehouse'];
            $resultData['item']         = json_encode($items);
        }
        $rs['data'] = $resultData;
        return $rs;
    }

    /**
     * delivery_search
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function delivery_search($sdf)
    {
        $delivery_bn = $sdf['delivery_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '发货单查询';

        $params = $this->_format_delivery_search_params($sdf);

        $rs = $this->__caller->call(WMS_SALEORDER_GET, $params, null, $title, 10, $delivery_bn);

        $failModel = app::get('erpapi')->model('api_fail');
        if ($rs['rsp'] == 'succ') {

            $result = $this->_deal_search_result($rs);
            if ($result['data']) {
                $rs = kernel::single('erpapi_router_response')->set_node_id($this->__channelObj->wms['node_id'])->set_api_name('wms.delivery.status_update')->dispatch($result['data']);

                //如果succ 删除fail_log
                if ($rs['rsp'] == 'succ') {
                    $failModel->delete(array('id' => $sdf['obj_id']));
                }
            }
        } else {
            $failModel->db->exec("UPDATE sdb_erpapi_api_fail SET fail_times=fail_times+1,msg_id='" . $rs['msg_id'] . "' WHERE id=" . $sdf['obj_id']);
        }
        return $rs;
    }

    protected function _format_delivery_search_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['out_order_code'],
        );
        return $params;
    }
}
