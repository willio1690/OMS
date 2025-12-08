<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_transfer extends openapi_api_params_abstract implements openapi_api_params_interface{

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
            'add'=>array(
                'io_bn'=>array('type'=>'string','required'=>'false','name'=>'入库单号','desc'=>''),
            	'name'=>array('type'=>'string','required'=>'true','name'=>'入库单名称','desc'=>'必填'),
            	'vendor'=>array('type'=>'string','required'=>'false','name'=>'供应商'),
                'branch_bn'=>array('type'=>'string','required'=>'true','name'=>'仓库编号','desc'=>'必填'),
                'delivery_cost'=>array('type'=>'number','required'=>'false','name'=>'出入库费用'),
                'memo'=>array('type'=>'string','required'=>'false','name'=>'备注'),
                'operator'=>array('type'=>'string','required'=>'false','name'=>'经办人'),
                't_type'=>array('type'=>'string','required'=>'true','name'=>'出入库类型','desc'=>'必填
                                                                                                E – 直接入库
                                                                                                A – 直接出库
                                                                                                G – 赠品入库
                                                                                                F – 赠品出库
                                                                                                K – 样品入库
                                                                                                J – 样品出库
                                                                                                Y – 分销入库
                                                                                                Z – 分销出库'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'明细','desc'=>'必填   格式为：bn:test1,name:测试1,price:10,nums:1;bn:test2,name:测试2,price:20,nums:2'),
                'extrabranch_bn'=>array('type'=>'string','required'=>'false','name'=>'外部仓库编号','desc'=>'（非必填）'),
                'extrabranch_name'=>array('type'=>'string','required'=>'false','name'=>'外部仓库名称','desc'=>'（非必填）当外部仓库编码和名称都有值时，依次判编码、名称，其一存在的看做选择的外部仓库并同时更新外部仓库数据项，都不存在的做新增外部仓库操作同时选择该外部仓库。'),
                'extrabranch_uname'=>array('type'=>'string','required'=>'false','name'=>'外部仓库联系人','desc'=>'（非必填）'),
                'extrabranch_email'=>array('type'=>'string','required'=>'false','name'=>'外部仓库邮箱','desc'=>'（非必填）'),
                'extrabranch_phone'=>array('type'=>'string','required'=>'false','name'=>'外部仓库电话','desc'=>'（非必填）'),
                'extrabranch_mobile'=>array('type'=>'string','required'=>'false','name'=>'外部仓库联系人手机','desc'=>'（非必填）'),
                'extrabranch_memo'=>array('type'=>'string','required'=>'false','name'=>'外部仓库备注','desc'=>'（非必填）'),
                'bill_type'=>array('type'=>'string','required'=>'false','name'=>'业务类型','desc'=>'（非必填）格式为：ASN'),
            ),
            'getList'=>array(
                    'original_bn'=>array('type'=>'string','required'=>'false','name'=>'原始单据号'),
                    'supplier_bn'=>array('type'=>'string','required'=>'false','name'=>'供应商编号'),
                    'branch_bn'=>array('type'=>'string','required'=>'false','name'=>'仓库编号'),
                    't_type'=>array('type'=>'string','required'=>'false','name'=>'出入库类型','desc'=>'
                                                                                            E – 直接入库
                                                                                            A – 直接出库
                                                                                            G – 赠品入库
                                                                                            F – 赠品出库
                                                                                            K – 样品入库
                                                                                            J – 样品出库
                                                                                            Y – 分销入库
                                                                                            Z – 分销出库'),
                    'start_time'=>array('type'=>'date','required'=>'true','name'=>'开始时间','desc'=>'例如2012-12-08 18:50:30'),
                    'end_time'=>array('type'=>'date','required'=>'true','name'=>'结束时间','desc'=>'例如2012-12-08 18:50:30'),
                    'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                    'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
            ),
            'getIsoList' => array(
                'start_time' => array('type' => 'date', 'required' => 'true', 'name' => '开始时间', 'desc' => '例如2012-12-08 18:50:30'),
                'end_time'   => array('type' => 'date', 'required' => 'true', 'name' => '结束时间', 'desc' => '例如2012-12-08 18:50:30'),
                't_type'     => array('type' => 'string', 'required' => 'false', 'name' => '出入库类型', 'desc' => 'E – 直接入库、A – 直接出库、G – 赠品入库、F – 赠品出、K – 样品入库、J – 样品出库'),
                'status'     => array('type' => 'string', 'required' => 'false', 'name' => '出入库状态', 'desc' => 'FINISH – 完成、PARTFINISH – 部分完成、CANCEL – 取消、NEW – 新建'),
                'branch_bn'  => array('type' => 'string', 'required' => 'false', 'name' => '仓库编号', 'desc' => '仓库编号'),
                'iso_bn'     => array('type' => 'string', 'required' => 'false', 'name' => '出入库单编号', 'desc' => '出入库单编号'),
                'bill_type'  => array('type' => 'string', 'required' => 'false', 'name' => '业务类型', 'desc' => '业务类型'),
                'bill_type_not'  => array('type' => 'string', 'required' => 'false', 'name' => '业务类型排除', 'desc' => '业务类型排除'),
                'page_no'    => array('type' => 'number', 'required' => 'false', 'name' => '页码', 'desc' => '默认1,第一页'),
                'page_size'  => array('type' => 'number', 'required' => 'false', 'name' => '每页数量', 'desc' => '最大100'),
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
                'add'=>array('name'=>'创建出入单','description'=>'创建一个直接出入库的指令'),
                'getList'=>array('name'=>'出入单明细','description'=>'出入单的出入库明细列表'),
                'getIsoList'=>array('name'=>'出入单列表','description'=>'查询出入库单列表'),
        );
        return $desccription[$method];
    }
}