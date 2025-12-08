<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_pda_pick extends openapi_api_params_abstract implements openapi_api_params_interface{

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
            'getDelivery'=>array(
                 'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                 'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                 'delivery_bn'=>array('type'=>'string','name'=>'发货单号','desc'=>'若无批次号，则必填。同批次，多个发货单，用半角分号隔开，比如 1612050000010;1612050000011'),
                 'ident'=>array('type'=>'string','name'=>'批次号','desc'=>'若传批次号，只能传一个；如无批次号，则通过上面的发货单号领取;')
            ),
            'bindbox'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'篮子明细','desc'=>'必填。&nbsp;格式：&nbsp;[{"delivery_bn":"1612050000001","basket_no ":"10"},{"delivery_bn":"1612132000002","basket_no ":"15}]')
            ),
            'getPickinList' => array(
                'pda_token' => array('type' => 'string', 'require'=>'true', 'name' => 'pda_token', 'desc' => '用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'batch_no' => array('type' => 'string', 'require' => 'true', 'name' => '批次号', 'desc' => '发货单批次号'),
            ),
            'getStockList'=>array(
                'pda_token'=>array('type'=>'string','require'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'status'=>array('type'=>'enum','value'=>array('ready'=>'可拣货'),'name'=>'状态','desc'=>''),
                'page_no'=>array('type'=>'number','required'=>'true','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'true','name'=>'每页最大数量','desc'=>'最大100'),
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
            'getDelivery'=>array('name'=>'配货认领接口','description'=>'领取发货单或备货单'),
            'bindbox'=>array('name'=>'发货单篮子号绑定接口','description'=>''),
            'getStockList'=>array('name'=>'获取备货单列表','description'=>'')
        );
        return $desccription[$method];
    }
}