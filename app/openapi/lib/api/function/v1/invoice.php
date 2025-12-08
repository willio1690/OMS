<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
+----------------------------------------------------------
 * Api接口[返回数据xml,json]
+----------------------------------------------------------
 * Time: 2014-03-18 $    update 20160608 by wangjianjun
 * [Ecos!] (C)2003-2014 Shopex Inc.
+----------------------------------------------------------
 */


class openapi_api_function_v1_invoice extends openapi_api_function_abstract implements openapi_api_function_interface
{

    //获取发票列表
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */

    public function getList($params, &$code, &$sub_msg)
    {

        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time   = $params['end_time'] ? strtotime($params['end_time']) : time();
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size  = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $page_size;
        }

        $filter = array(
            'create_time|bthan' => $start_time,
            'create_time|sthan' => $end_time,
        );
        if ($params['last_modify_start_time']) {
            $filter['last_modify|bthan'] = strtotime($params['last_modify_start_time']);
        }
        if ($params['last_modify_end_time']) {
            $filter['last_modify|sthan'] = strtotime($params['last_modify_end_time']);
        }
        //开票状态
        $is_status = trim($params['is_status']);
        if ($is_status == "") {
            //没有填
        } else {
            $is_status = intval($is_status);
            if (in_array($is_status, array("0", "1", "2"))) {
                $filter["is_status"] = $is_status;
            }
        }

        //开票方式
        $mode = trim($params['mode']);
        if ($mode == "") {
            //没有填
        } else {
            $mode = intval($mode);
            if (in_array($mode, array("0", "1"))) {
                $filter["mode"] = $mode;
            }
        }

        $result = kernel::single('openapi_data_original_invoice')->getList($filter, $offset, $page_size);

        return $result;

    }

    //更新订单纸质发票的打印状态
    /**
     * 更新
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function update($params, &$code, &$sub_msg)
    {

        //检查订单号 店铺id 发票号
        $order_bn   = trim($params["order_bn"]);
        $shop_id    = trim($params["shop_id"]);
        $invoice_no = trim($params["invoice_no"]);
        if (!$order_bn || !$shop_id || !$invoice_no) {
            $result = array(
                "rsp" => "fail",
                "msg" => "请填写必填参数",
            );
            return $result;
        }

        //获取发票信息
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice = $mdlInOrder->dump(array("order_bn" => $order_bn, "shop_id" => $shop_id));

        if (empty($rs_invoice)) {
            $result = array(
                "rsp" => "fail",
                "msg" => "此订单发票信息不存在",
            );
            return $result;
        }

        //检查必须为纸质发票 如为电子发票这里不能更新发票打印数据
        if (intval($rs_invoice["mode"]) != '0') {
            $result = array(
                "rsp" => "fail",
                "msg" => "必须为纸质发票",
            );
            return $result;
        }

        //作废发票不做更新
        if (intval($rs_invoice["is_status"]) == 2) {
            $result = array(
                "rsp" => "fail",
                "msg" => "此发票已作废",
            );
            return $result;
        }

        $result = kernel::single('openapi_data_original_invoice')->update($rs_invoice, $invoice_no);
        return $result;

    }

    //add必须存在
    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params, &$code, &$sub_msg)
    {}

    //获取发票列表
    /**
     * 获取ResultList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getResultList($params, &$code, &$sub_msg)
    {
        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time   = $params['end_time'] ? strtotime($params['end_time']) : time();
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $page_size  = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        $filter = array(
            'last_modified|bthan' => $start_time,
            'last_modified|sthan' => $end_time,
        );

        $invMdl = app::get('invoice')->model('order');
        if ($params['order_bn']) {
            $invList = $invMdl->getList('id', ['order_bn' => $params['order_bn']]);

            $filter['id'] = $invList ? array_column($invList, 'id') : 0;
        }

        return kernel::single('openapi_data_original_invoice')->getResultList($filter, ($page_no - 1) * $page_size, $page_size);
    }

}
