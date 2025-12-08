<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 电子发票-请求接口函数实现类
 *
 * @author xiayuanjun<xiayuanjun@shopex.cn>
 * @version 0.1
 */
class erpapi_invoice_request_redapply extends erpapi_invoice_request_abstract
{

    /**
     * 红字申请单创建
     * 
     * @param array $sdf
     * @return array
     */

    public function create($sdf)
    {
        //$this->__resultObj->is_mock = true;
        //$this->__resultObj->mock_method = INVOICE_REVERSE_APPLICATION_CREATE;

        // 获取红冲申请单创建参数
        $params = $this->_get_create_params($sdf);
        //$params['use_mock']='1';

        // 获取红冲申请单日志单号
        $primary_bn = $this->_get_create_primary_bn($sdf);

        if (!$params) {
            return array ('rsp' => 'fail', 'err_msg' => '创建数据为空');
        }

        // 获取红冲申请单回调参数
        $callbackParams = $this->_get_create_callback_params($sdf);

        // 获取调用模式
        $isAsync = $this->_getCallMode(__FUNCTION__);

        if ($isAsync) {
            $callback = array (
                'class'  => get_class($this),
                'method' => 'create_callback',
                'params' => $callbackParams,
            );
        } else {
            $callback = array ();
        }

        $rs = $this->__caller->call(INVOICE_REVERSE_APPLICATION_CREATE, $params, $callback, '创建发票红冲申请单', 30, $primary_bn, true);

        if (!$isAsync) {
            $this->create_callback($rs, $callbackParams);
        }

        return $rs;
    }

    /**
     * 红字申请单确认 todo 待实现
     * 
     * @param array $sdf
     * @return array
     */
    public function confirm($sdf)
    {
        return;
    }

    /**
     * 红字申请单查询
     * 
     * @param array $sdf
     * @return array
     */
    public function query($sdf)
    {
        // 获取红冲申请单创建参数
        $params = $this->_get_query_params($sdf);
        //$params['use_mock'] = '1';

        // 获取红冲申请单日志单号
        $primary_bn = $this->_get_query_primary_bn($sdf);

        $callback = array ();

        $rs = $this->__caller->call(INVOICE_RED_APPLY_QUERY, $params, $callback, '查询发票红冲申请单', 30, $primary_bn, true);

        return $rs;
    }

    /**
     * 红字申请单创建回调
     * @param $result
     * @param $callbackParams
     * @return void
     */
    public function create_callback($result, $callbackParams)
    {
        // 没有数据, 则不处理
        if (!$result['data']) {
            return $this->callback($result, $callbackParams);
        }

        // 响应类传入参数
        $params = is_string($result['data']) ? json_decode($result['data'], true) : $result['data'];

        // 因成功时没有具体回参, 故于event内处理具体业务,不做模拟回调
        return $this->callback($result, $callbackParams);
    }

    /**
     * 获取红字申请单请求参数
     * @param $sdf
     * @return void
     */
    protected function _get_create_params($sdf)
    {

        return $sdf['request_params'] ?? [];
    }

    /**
     * 获取红字申请单日志业务单号
     * @param $sdf
     * @return void
     */
    protected function _get_create_primary_bn($sdf)
    {
        return $sdf['order']['order_bn'] ?? "";
    }

    /**
     * 获取红字申请单回调参数
     * @param $sdf
     * @return void
     */
    protected function _get_reverse_application_create_callback_params($sdf)
    {
        return [];
    }

    /**
     * 发票获取
     * @param $sdf
     * @return array
     */
    protected function _get_create_callback_params($sdf)
    {
        if (!isset($sdf['order']) || !$sdf['order']) {
            return [];
        }

        $order   = $sdf['order'];
        $keyList = ['id', 'serial_no', 'order_bn', 'shop_id'];
        return array_intersect_key($order, array_flip($keyList));
    }

    private function _get_confirm_callback_params(array $sdf) {}

    private function _get_confirm_params(array $sdf) {}

    private function _get_confirm_primary_bn(array $sdf) {}

    /**
     * 获取红字申请单请求参数
     * @param $sdf
     * @return void
     */
    protected function _get_query_params($sdf)
    {

        return $sdf['request_params'] ?? [];
    }

    private function _get_query_primary_bn($sdf)
    {
        return $sdf['order']['order_bn'] ?? [];
    }
}
