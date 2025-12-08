<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/1/13
 * @describe pda 发货单查询
 */
class openapi_api_params_v1_pda_delivery extends openapi_api_params_abstract implements openapi_api_params_interface
{

    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */

    public function checkParams($method, $params, &$sub_msg)
    {
        if (parent::checkParams($method, $params, $sub_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取AppParams
     * @param mixed $method method
     * @return mixed 返回结果
     */
    public function getAppParams($method)
    {
        $params = array(
            'getList'      => array(
                'pda_token'       => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code'     => array('type' => 'string', 'required' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'status'          => array(
                    'type'  => 'enum',
                    'value' => array('' => '状态选择', 'ready' => '可拣货', 'picked' => '可校验', 'checked' => '可发货'),
                    'name'  => '发货单状态',
                    'desc'  => ''),
                'delivery_bn'     => array('type' => 'string', 'require' => 'false', 'name' => '发货单号', 'desc' => ''),
                'delivery_ident'  => array('type' => 'string', 'require' => 'false', 'name' => '批次号', 'desc' => ''),
                'delivery_panier' => array('type' => 'string', 'require' => 'false', 'name' => '篮子号', 'desc' => ''),
                'page_no'         => array('type' => 'number', 'require' => 'false', 'name' => '页码', 'desc' => '默认1,第一页'),
                'page_size'       => array('type' => 'number', 'require' => 'false', 'name' => '每页最大数量', 'desc' => '最大100'),
            ),
            'updateStatus' => array(
                'pda_token'   => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code' => array('type' => 'string', 'required' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'status'      => array(
                    'type'  => 'enum',
                    'value' => array('picked' => '拣货完成' /*'checked'=>'校验完成','delivery'=>'发货发货完成'*/),
                    'name'  => '发货单状态',
                    'desc'  => '',
                ),
                'delivery_bn' => array('type' => 'string', 'require' => 'true', 'name' => '发货单号', 'desc' => '例：1701130000016;1701130000017'),
                'batch_no'    => array('type' => 'string', 'name' => '批次号', 'desc' => '例：1-70321-0028'),

            ),
            'check'        => array(
                'pda_token'    => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code'  => array('type' => 'string', 'required' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                // 'check_type'  =>array('type'=>'string','require'=>'true','name'=>'校验类型','desc'=>'整单校验或逐个校验'),
                'logi_no'  => array('type' => 'string', 'require' => 'true', 'name' => '物流单号', 'desc' => ''),
                'serial_data'  => array('type' => 'string', 'require' => 'false', 'name' => '唯一码', 'desc' => ''),
                'verify_items' => array('type' => 'string', 'require' => 'false', 'name' => '校验明细', 'desc' => '格式：[{"bn":"bn1","verify_nums":"1"},{"bn":"bn2","verify_nums":"1"}]'),
                'status'       => array('type' => 'string', 'require' => 'true', 'name' => '校验状态', 'desc' => 'FINISH:完成,PARTIN:部分'),
            ),
            'batchCheck'   => array(
                'pda_token'     => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code'   => array('type' => 'string', 'required' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'delivery_info' => array('type' => 'string', 'require' => 'true', 'name' => '发货单信息', 'desc' => '格式：[{"delivery_bn":"1801172000001","status":"FINISH","verify_items":[{"bn":"6956195709331","verify_nums":"2"},{"bn":"TM14060900021","verify_nums":"1"}],"serial_data":[{"bn":"6956195709331","serial_number":"2153123"},{"bn":"TM14060900021","serial_number":"13874234"}]},{"delivery_bn":"1801172000002","status":"PARTIN","verify_items":[{"bn":"6956195709331","verify_nums":"1"}]}]'),
            ),
            'consign'      => array(
                'pda_token'   => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code' => array('type' => 'string', 'require' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'logi_no'     => array('type' => 'string', 'require' => 'false', 'name' => '运单号', 'desc' => '运单号(发货单号与运单号不能同时为空)'),
                'delivery_bn' => array('type' => 'string', 'require' => 'false', 'name' => '发货单号', 'desc' => '发货单号(发货单号与运单号不能同时为空)'),
                'weight'      => array('type' => 'number', 'require' => 'false', 'name' => '包裹重量', 'desc' => '包裹重量(g)，不传取首重'),
            ),
            'printCPCL'        => array(
                'pda_token'    => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code'  => array('type' => 'string', 'require' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'delivery_bn'  => array('type' => 'string', 'require' => 'true', 'name' => '发货单号', 'desc' => '发货单号'),
                'printer_name' => array('type' => 'string', 'require' => 'true', 'name' => '打印机名称', 'desc' => '打印机名称'),
            ),
            'consign' => array(
                'pda_token' => array('type' => 'string', 'require' => 'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code' => array('type' => 'string', 'require' => 'true', 'name' => '机器码', 'desc' => '设备唯一编码(必填项)'),
                'logi_no' => array('type' => 'string', 'require' => 'false', 'name' => '运单号', 'desc' => '运单号(发货单号与运单号不能同时为空)'),
                'delivery_bn' => array('type' => 'string', 'require' => 'false','name' => '发货单号', 'desc' => '发货单号(发货单号与运单号不能同时为空)'),
                'weight' => array('type' => 'number', 'require' => 'false', 'name' => '包裹重量', 'desc' => '包裹重量(g)，不传取首重'),
            ),
        );

        return $params[$method];
    }

    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method)
    {
        $desccription = array(
            'getList'      => array('name' => '发货单查询', 'description' => '获取发货单列表'),
            'updateStatus' => array('name' => '发货单更新', 'description' => '更新发货单状态'),
            'check'        => array('name' => '发货单校验', 'description' => '发货校验'),
            'batchCheck'   => array('name' => '批量发货校验', 'description' => '批量发货校验'),
            'consign'      => array('name' => '发货单发货', 'description' => '发货单发货'),
            'printCPCL'        => array('name' => '发货单打印', 'description' => '发货单打印'),
        );
        return $desccription[$method];
    }
}
