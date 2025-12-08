<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @describe pda售后退换货相关
 * @author pangxp
 */
class openapi_api_function_v1_pda_reship extends openapi_api_function_v1_pda_abstract{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */

    public function getList($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1) * $limit;
        }

        $filter = array();
        if (!empty($params['logi_no'])) {
            $filter['logi_no'] = trim($params['logi_no']);
        }
        if (!empty($params['order_bn'])) {
            $filter['order_bn'] = trim($params['order_bn']);
        }
        if (!empty($params['ship_name'])) {
            $filter['ship_name'] = trim($params['ship_name']);
        }
        if (!empty($params['ship_mobile'])) {
            $filter['ship_mobile'] = trim($params['ship_mobile']);
        }
        if (!empty($params['member_uname'])) {
            $filter['member_uname'] = trim($params['member_uname']);
        }
        $result = kernel::single('openapi_data_original_pda_reship')->getList($filter, $offset, $limit);
        if ($result['state'] == 0) {
            foreach ($result['lists'] as &$list) {
                $list['ship_name'] = $this->charFilter($list['ship_name']);
                $list['ship_tel'] = $this->charFilter($list['ship_tel']);
                $list['ship_mobile'] = $this->charFilter($list['ship_mobile']);
                $this->_write_log('pda获取退换货列表', $list['reship_bn'], 'success', $params, $result);
            }
        }
        return $result;

    }

    /**
     * 获取DetailList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getDetailList($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1) * $limit;
        }

        $filter['reship_id'] = trim(intval($params['reship_id']));
        !empty($params['item_id']) && $filter['item_id'] = trim(intval($params['item_id']));
        $filter['return_type'] = 'return'; // 取退的明细
        $result = kernel::single('openapi_data_original_pda_reship')->getDetailList($filter, $offset, $limit);
        if ($result['state'] == 0) {
            foreach ($result['lists'] as &$list) {
                $list['specifications'] = $this->charFilter($list['specifications']);
                $list['delivery_factory'] = $this->charFilter($list['delivery_factory']);
                $list['supplier'] = $this->charFilter($list['supplier']);
                $list['branch_name'] = $this->charFilter($list['branch_name']);
                $list['remark'] = $this->charFilter($list['remark']);
                // $list['content'] = $this->charFilter($list['content']);
                // $list['memo'] = $this->charFilter($list['memo']);
            }
            $this->_write_log('pda获取退换货明细', $result['lists'][0]['reship_bn'], 'success', $params, $result);
            if ($result['lists'][0]['reship_id']) {
                $op_info = kernel::single('ome_func')->getDesktopUser();
                $oOperation_log = app::get('ome')->model('operation_log');
                $oOperation_log->write_log('reship_getlist@ome', $result['lists'][0]['reship_id'], 'pda获取退换货明细', time(), array('op_id' => $op_info['op_id'], 'op_name' => $op_info['op_name']));
            }
        }
        return $result;

    }

    /**
     * normalReturn
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function normalReturn($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $oReship = app::get('ome')->model('reship');
        $order = $oReship->getList('reship_id, reship_bn', array('reship_id' => $params['reship_id']));
        if ( empty($order) ) {
            $result = array(
                'state' => 1,
                'message' => 'reship_id: ' . $params['reship_id'] . ', 数据库中退货单不存在',
            );
            $this->_write_log('pda正常退货', $params['reship_id'], 'success', $params, $result);
            return $result;
        }

        $result = kernel::single('openapi_data_original_pda_reship')->normalReturn($params);
        $this->_write_log('pda正常退货', $order[0]['reship_bn'], 'success', $params, $result);
        return $result;
    }

    /**
     * abnormalReturn
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function abnormalReturn($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $oReship = app::get('ome')->model('reship');
        $order = $oReship->getList('reship_id, reship_bn', array('reship_id' => $params['reship_id']));
        if ( empty($order) ) {
            $result = array(
                'state' => 1,
                'message' => 'reship_id: ' . $params['reship_id'] . ', 数据库中退货单不存在',
            );
            $this->_write_log('pda异常退货', $params['reship_id'], 'success', $params, $result);
            return $result;
        }

        $result = kernel::single('openapi_data_original_pda_reship')->abnormalReturn($params);
        $this->_write_log('pda异常退货', $order[0]['reship_bn'], 'success', $params, $result);
        return $result;
    }

    /**
     * printReturn
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function printReturn($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $oReship = app::get('ome')->model('reship');
        $reship = $oReship->getList('reship_id, reship_bn', array('reship_id' => $params['reship_id']));
        if ( empty($reship) ) {
            $result = array(
                'state' => 1,
                'message' => 'reship_id: ' . $params['reship_id'] . ', 数据库中退货单不存在',
            );
            $this->_write_log('售后信息打印', $params['reship_id'], 'success', $params, $result);
            return $result;
        }

        $result = array(
            'state' => 0,
            'message' => '记录' . $reship[0]['reship_bn'] . ': pda打印',
        );

        $this->_write_log('售后信息打印', $reship[0]['reship_bn'], 'success', $params, $result);
        if ($params['reship_id']) {
            $op_info = kernel::single('ome_func')->getDesktopUser();
            $oOperation_log = app::get('ome')->model('operation_log');
            $oOperation_log->write_log('reship_print@ome', $params['reship_id'], 'pda打印售后信息', time(), array('op_id' => $op_info['op_id'], 'op_name' => $op_info['op_name']));
        }
        return $result;
    }

    /**
     * forward
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function forward($params, &$code, &$sub_msg)
    {
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        $data = array();
        $data['reship_id'] = trim(intval($params['reship_id']));
        $data['item_id'] = trim(intval($params['item_id']));
        $oReship = app::get('ome')->model('reship');
        $oReshipItems = app::get('ome')->model('reship_items');
        $reship_data = $oReship->getList('reship_id, reship_bn', array('reship_id' => $data['reship_id']));
        if ( empty($reship_data) ) {
            $result = array(
                'state' => 1,
                'message' => 'reship_id: ' . $data['reship_id'] . ', 数据库中退货单不存在',
            );
            $this->_write_log('pda操作转寄', $data['reship_id'], 'success', $data, $result);
            return $result;
        }

        $reship_items = $oReshipItems->getList('item_id, bn', array('reship_id' => $data['reship_id'], 'item_id' => $data['item_id']));
        if ( empty($reship_items) ) {
            $result = array(
                'state' => 1,
                'message' => 'reship_id: ' . $data['reship_id'] . ', 数据库中退货单明细item_id:' . $data['item_id'] . '不存在',
            );
            $this->_write_log('pda操作转寄', $data['reship_id'], 'success', $data, $result);
            return $result;
        }

        $data['bn'] = $reship_items[0]['bn'];
        $result = kernel::single('openapi_data_original_pda_reship')->forward($data);
        $this->_write_log('pda操作转寄', $reship_data[0]['reship_bn'], 'success', $data, $result);
        if ($data['reship_id']) {
            $op_info = kernel::single('ome_func')->getDesktopUser();
            $oOperation_log = app::get('ome')->model('operation_log');
            $oOperation_log->write_log('reship_getlist@ome', $data['reship_id'], 'pda操作转寄:' . $reship_items[0]['bn'], time(), array('op_id' => $op_info['op_id'], 'op_name' => $op_info['op_name']));
        }
        return $result;

    }


}