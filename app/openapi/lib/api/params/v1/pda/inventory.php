<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_pda_inventory extends openapi_api_params_abstract implements openapi_api_params_interface{
    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function checkParams($method,$params,&$sub_msg){
        if(parent::checkParams($method,$params,$sub_msg)){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取AppParams
     * @param mixed $method method
     * @return mixed 返回结果
     */
    public function getAppParams($method){
        $params = array(
            'create'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'branch_bn' => array('type'=>'string','required'=>'true','name'=>'仓库编号','desc'=>'必填'),
                'inventory_name'=>array('type'=>'string','required'=>'true','name'=>'盘点名称','desc'=>'必填'),
                'op_name'=>array('type'=>'string','required'=>'true','name'=>'盘点人'),
                'add_time'=>array('type'=>'string','name'=>'业务日期','desc'=>'格式：2017-01-01  不填，则为当天日期'),
                'inventory_checker'=>array('type'=>'string','name'=>'复核人'),
                'warehousing_dept'=>array('type'=>'string','name'=>'账务负责人'),
                'finance_dept'=>array('type'=>'string','name'=>'仓库负责人'),
                'inventory_type'=>array('type'=>'number','name'=>'盘点类型','desc'=>'默认部分盘点   &nbsp;2全盘 ，3部分盘点 ，4期初'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'盘点明细','desc'=>'必填 格式:&nbsp;[{"bn":"shopex1","num":"10"},{"bn":"shopex2","num":"20"}]')
            ),
           'update'=>array(
               'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
               'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
               'inventory_bn'=>array('type'=>'string','required'=>'true','name'=>'盘点单号','desc'=>'必填'),
               'items'=>array('type'=>'string','required'=>'true','name'=>'盘点明细','desc'=>'必填。 格式:&nbsp;[{"bn":"shopex1","num":"10"},{"bn":"shopex2","num":"20"}]')
           ),
            'getList'=>array(
                'pda_token'    => array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'  => array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'branch_bn'    => array('type'=>'string','name'=>'仓库编号'),
                'inventory_bn' => array('type'=>'string','name'=>'盘点单号','desc'=>'如果填写了盘点单号，可以不填时间'),
                'status'       => array('type'=>'string', 'name' => '状态', 'desc' => '1:未确认、2:已确认、3:作废、4:盘点中'),
                'start_time'   => array('type'=>'date','name'=>'开始时间','desc'=>'例如2017-01-01 18:50:30'),
                'end_time'     => array('type'=>'date','name'=>'结束时间','desc'=>'例如2017-01-31 23:59:59'),
                'page_no'      => array('type'=>'number','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'    => array('type'=>'number','name'=>'每页数量','desc'=>'最大100'),
            ),
            'getExpireBnInfo'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'branch_id' => array('type' => 'number', 'name' => '仓库id', 'required' => 'true', 'desc'=>'仓库id'),
                'inventory_id' => array('type' => 'number', 'name' => '盘点id', 'required' => 'false', 'desc'=>'盘点id'),
                'selecttype' => array('type' => 'string', 'name' => '查询类型', 'required' => 'true', 'desc'=>'barcode,bn'),
                'barcode' => array('type' => 'string', 'name' => '基础物料', 'required' => 'true', 'desc'=>'基础物料'),
                'search_expire_bn' => array('type' => 'string', 'name' => '物料保质期编码', 'required' => 'false', 'desc'=>'物料保质期编码'),
                'expiring_date_from' => array('type' => 'date', 'name' => '过期日期开始时间', 'required' => 'false', 'desc'=>'过期日期开始时间,例如2021-05-20 14:00:00'),
                'expiring_date_to' => array('type' => 'date', 'name' => '过期日期结束时间', 'required' => 'false', 'desc'=>'过期日期结束时间,例如2021-05-20 14:00:00'),
                'production_date_from' => array('type' => 'date', 'name' => '生产日期开始时间', 'required' => 'false', 'desc'=>'生产日期开始时间,例如2021-05-20 14:00:00'),
                'production_date_to' => array('type' => 'date', 'name' => '生产日期结束时间', 'required' => 'false', 'desc'=>'生产日期结束时间,例如2021-05-20 14:00:00'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大1000'),
            ),
            'getStorageLife'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'inventory_id' => array('type' => 'number', 'name' => '盘点id', 'required' => 'true', 'desc'=>'盘点id'),
                'item_id' => array('type' => 'number', 'name' => '明细id', 'required' => 'false', 'desc'=>'明细id'),
                'bm_id' => array('type' => 'number', 'name' => '物料id', 'required' => 'true', 'desc'=>'物料id'),
            ),
        );
        return $params[$method];
    }
    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method){
        $desccription = array(
            'create'=>array('name'=>'盘点添加','description'=>'盘点添加'),
            'update'=>array('name'=>'盘点更新','description'=>'盘点更新'),
            'getList'=>array('name'=>'盘点获取','description'=>'盘点获取'),
            'getExpireBnInfo' => array('name' => '获取保质期批次', 'description' => '获取保质期批次'),
            'getStorageLife' => array('name' => '获取关联的保质期列表', 'description' => '获取关联的保质期批次信息'),
        );
        return $desccription[$method];
    }
}