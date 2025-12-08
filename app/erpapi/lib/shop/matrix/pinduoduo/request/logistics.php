<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2019-10-16 16:37:20
 * @describe 物流相关 请求接口类
 */
class erpapi_shop_matrix_pinduoduo_request_logistics extends erpapi_shop_request_logistics {
    //需要判断推荐物流的省份
    protected $privinceId = ['5','9','19','20','21','28','29'];
    //物流ID对应编码
    protected $logiIdCode = [
        '1' => ['code'=>'STO','name'=>'申通快递'],
        '3' => ['code'=>'BEST','name'=>'百世快递'],
        '44' => ['code'=>'SF','name'=>'顺丰快递'],
        '85' => ['code'=>'YTO','name'=>'圆通快递'],
        '115' => ['code'=>'ZTO','name'=>'中通快递'],
        '118' => ['code'=>'EMS','name'=>'邮政EMS'],
        '121' => ['code'=>'YUNDA','name'=>'韵达快递'],
        '131' => ['code'=>'DBL','name'=>'德邦快递'],
        '132' => ['code'=>'POSTB','name'=>'邮政快递包裹'],
        '384' => ['code'=>'JITU','name'=>'极兔速递'],
    ];

    /**
     * 获取CorpServiceCode
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getCorpServiceCode($sdf) {
        $params = array(
            'company_code' => $sdf['cp_code']
        );
        $title = '拼多多获取物流商服务类型';
        $result = $this->__caller->call(STORE_HQEPAY_ORDERSERVICE,$params,array(),$title, 10, $params['company_code']);
        return $result;
    }

    /**
     * 获取Recommend
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getRecommend($sdf) {
        if(!in_array($sdf['platform_province_id'], $this->privinceId)) {
            return $this->succ('不在偏远地区');
        }
        $params = array(
            'city_id' => json_encode([$sdf['platform_city_id']], 1)
        );
        $title = '拼多多获取推荐物流';
        $result = $this->__caller->call(SHOP_LOGISTICS_RECOMMEND,$params,array(),$title, 10, $sdf['order_bn']);
        if(!$result['data']) {
            return ['rsp'=>'fail', 'msg'=>$result['msg'] ? : '没有返回物流公司ID'];
        }
        $data = json_decode($result['data'], 1);
        $result['data'] = [];
        $result['data']['code'] = ['表示获取物流返回成功实际不使用'];
        $result['data']['name'] = [];
        foreach ($data[0]['company_id'] as $key => $value) {
            if($this->logiIdCode[$value]) {
                $result['data']['code'][] = $this->logiIdCode[$value]['code'];
                $result['data']['name'][] = $this->logiIdCode[$value]['name'];
            }
        }
        return $result;
    }
}