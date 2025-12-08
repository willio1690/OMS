<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @describe pda售后退换货相关
 * @author pangxp
 */
class openapi_api_params_v1_pda_reship extends openapi_api_params_abstract implements openapi_api_params_interface{

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
            'getList'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'logi_no'=>array('type'=>'string','required'=>'false','name'=>'物流单号','desc'=>'非必填'),
                'order_bn'=>array('type'=>'string','required'=>'false','name'=>'订单号','desc'=>'非必填'),
                'ship_name'=>array('type'=>'string','required'=>'false','name'=>'收货人姓名','desc'=>'非必填'),
            	'ship_mobile'=>array('type'=>'string','required'=>'false','name'=>'收货人手机','desc'=>'非必填'),
                'member_uname'=>array('type'=>'string','required'=>'false','name'=>'用户名','desc'=>'非必填'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
            ),
            'getDetailList'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'reship_id'=>array('type'=>'int','required'=>'true','name'=>'退货单id','desc'=>'必填'),
                'item_id'=>array('type'=>'int','required'=>'false','name'=>'退货单明细id','desc'=>'非必填'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
            ),
            'normalReturn'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'data'=>array('type'=>'string','required'=>'true','name'=>'提交数据','desc'=>'必填 格式为：product_id:1, remark:remark1, quantity:quantity, abnormalBit:0, reason:reason1, scenePicture:scenePictureInfo scenePictureInfo格式为：url1, url2, url3, number:06-15-1'),
                'reship_id'=>array('type'=>'int','required'=>'true','name'=>'退货单id','desc'=>'必填'),
            ),
            'abnormalReturn'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'data'=>array('type'=>'string','required'=>'true','name'=>'提交数据','desc'=>'必填 格式为：product_id:1, remark:remark1, quantity:quantity, abnormalBit:0, reason:reason1, scenePicture:scenePictureInfo scenePictureInfo格式为：url1, url2, url3'),
                'reship_id'=>array('type'=>'int','required'=>'true','name'=>'退货单id','desc'=>'必填'),
            ),
            'printReturn'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'reship_id'=>array('type'=>'int','required'=>'true','name'=>'退货单id','desc'=>'必填'),
            ),
            'forward'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'reship_id'=>array('type'=>'int','required'=>'true','name'=>'退货单id','desc'=>'必填'),
                'item_id'=>array('type'=>'int','required'=>'true','name'=>'退货单明细id','desc'=>'必填'),
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
                'getList'=>array('name'=>'退换货列表','description'=>'退换货列表'),
                'getDetailList'=>array('name'=>'退换货明细','description'=>'退换货明细'),
                'normalReturn'=>array('name'=>'正常退货接口','description'=>'正常退货接口'),
                'abnormalReturn'=>array('name'=>'异常退货明细','description'=>'异常退货接口'),
                'printReturn'=>array('name'=>'售后信息打印','description'=>'售后信息打印'),
                'forward'=>array('name'=>'退货单转寄','description'=>'退货单转寄'), // 场景：不是自己仓库的商品需要进行转寄功能。打印出的转寄标签贴在包裹上。方便识别
        );
        return $desccription[$method];
    }
}