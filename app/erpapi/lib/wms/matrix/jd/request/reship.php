<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author
 * @describe 京东退货单
 */
class erpapi_wms_matrix_jd_request_reship extends erpapi_wms_request_reship
{

    protected function _getNextObjType()
    {
        return 'search_reship';
    }

    protected function _format_reship_create_params($sdf)
    {
        $params                  = parent::_format_reship_create_params($sdf);
        $params['department_no'] = app::get('wmsmgr')->getConf('department_no_' . $this->__channelObj->wms['channel_id']);
        $params['order_type']    = 'IN_EXCHANGE';
        return $params;
    }

    /**
     * reship_create_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */

    public function reship_create_callback($response, $callback_params)
    {
        $data             = @json_decode($response['data'], true);
        $tmp              = array('wms_order_code' => $data['msg']['eclpRtwNo']);
        $response['data'] = json_encode($tmp);
        parent::reship_create_callback($response, $callback_params);
    }

    /**
     * reship_cancel
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_cancel($sdf)
    {
        return $this->error('接口方法不存在', 'w402');
    }

    protected function _format_search_params($sdf)
    {
        $params                       = parent::_format_search_params($sdf);
        $params['order_type']         = 'IN_EXCHANGE';
        $params['warehouse_code']     = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        $params['erp_out_order_code'] = $sdf['stockin_bn'];

        return $params;
    }

    protected function _get_search_api()
    {
        // 当物流类型为沧海物流时-调用WMS_RETURNORDER_GET
        if ($this->__channelObj->wms['addon']['wms_type'] == '1') {
            return WMS_RETURNORDER_GET;
        }
        return WMS_INORDER_GET;
    }

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
            $itemList  = json_decode($data['item'], true);
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
            if ($this->__channelObj->wms['addon']['wms_type'] == '1') {
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
            }
            // 进行数据处理
            $items = array_values($sku_list);

            $resultData['type']         = $data['type'];
            $resultData['status']       = $data['status'];
            $resultData['remark']       = $data['remark'];
            $resultData['reship_bn']    = $data['stock_bn'];
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
     * 退货单查询
     * 
     * @return void
     * @author
     * */
    // public function reship_search($sdf)
    // {
    //     $title = $this->__channelObj->wms['channel_name'] . '退货单查询';

    //     $params = $this->_format_search_params($sdf);
    //     $rs     = $this->__caller->call(WMS_RETURNORDER_GET, $params, null, $title, 10, $params['orig_order_code']);
    //     if ($rs['rsp'] == 'succ') {
    //         $rs['data'] = json_decode($rs['data'], true);
    //     }
    //     return $rs;
    // }

        /**
     * reship_searchlist
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reship_searchlist($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '退货单列表查询';

        $params = array(

            'deptNo'     => app::get('wmsmgr')->getConf('department_no_' . $this->__channelObj->wms['channel_id']),
            'status'     => '200',
            'start_date' => $sdf['start_time'],
            'end_date'   => $sdf['end_time'],
            'page_no'    => '1',
            'page_size'  => '10',

        );
        $rs = $this->__caller->call('store.wms.returnorder.code.get', $params, null, $title, 10, '');
        if ($rs['rsp'] == 'succ') {
            $rs['data'] = json_decode($rs['data'], true);
        }
        return $rs;
    }

}
