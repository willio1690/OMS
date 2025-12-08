<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 获取平台店铺售后退货地址库
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_luban_request_logistics extends erpapi_shop_request_logistics
{
    /**
     * 获取平台店铺售后退货地址库
     * 
     * @param string $search_type 搜索类型(抖音平台不用此字段)
     * @param int $page 页码
     * @return array
     */

    public function searchAddress($search_type='', $page=0)
    {
        $oAddress = app::get('ome')->model('return_address');
        
        $shop_id = $this->__channelObj->channel['shop_id'];
        $shop_bn = $this->__channelObj->channel['shop_bn'];
        $shop_type = $this->__channelObj->channel['shop_type'];
        
        $title = '店铺('.$this->__channelObj->channel['name'].')获取地址库列表';
        
        $page = intval($page);
        $page = ($page ? $page : 1);
        $page_size = 20; //默认一次拉取20条
        
        //params
        $params = array(
                'shop_id' => $shop_id,
                'shop_type' => $shop_type,
                'page_no' => $page,
                'page_size' => $page_size,
        );
        
        //request
        $callback = array();
        $rsp = $this->__caller->call(SHOP_GET_ADDRESS_LIST, $params, $callback, $title, 15, $shop_bn);
        if($rsp['rsp'] != 'succ'){
            return $rsp;
        }
        
        //保存至本地
        $dataList = json_decode($rsp['data'], true);
        $address_list = $dataList['address_list'];
        if($address_list){
            //[拉取第一页时]删除该店铺下的所有平台地址
            if($page == 1){
                $oAddress->delete(array('shop_id'=>$shop_id, 'reship_id'=>0));
            }
            
            //list
            foreach ($address_list as $key => $val)
            {
                $is_default = ($val['is_default']=='1' ? 'true' : 'false');
                
                //check
                if(empty($val['address_id'])){
                    continue;
                }
                
                //data
                $data = array(
                        'cancel_def' => $is_default, //是否默认退货地址
                        //'get_def' => $is_default, //是否默认取货地址
                        'area_id' => 0, //区域ID
                        'contact_id' => $val['address_id'], //地址库ID
                        'shop_type' => $shop_type,
                        'shop_id' => $shop_id,
                        'province' => $val['receiver_provinc'], //省
                        'city' => $val['receiver_city'], //市
                        'country' => $val['receiver_district'], //区
                        'street' => $val['receiver_street'], //街道
                        'addr' => $val['receiver_detail'], //详细地址
                        'zip_code' => $val['zip_code'], //地区邮政编码
                        'phone' => $val['phone'], //电话号码
                        'mobile_phone' => $val['mobile_phone'], //手机号码
                        'contact_name' => $val['reciever_name'], //联系人姓名
                        'seller_company' => '', //公司名称
                        'platform_create_time' => ($val['create_time'] ? $val['create_time'] : time()),
                        'platform_update_time' => ($val['update_time'] ? $val['update_time'] : time()),
                        'modify_date' => time(),
                        'add_type' => 'shop', //创建类型为：店铺平台
                        'wms_type' => '0', //WMS仓储类型,默认设置为：0
                );
                $saveRs = $oAddress->save($data);
            }
        }
        
        return $rsp;
    }

    //物流对应编码 yuantong、zhongtong、yunda、shunfeng、jd、jtexpress、shentong
    protected $logiMapCode = [
        'shentong' => ['code'=>'STO','name'=>'申通快递'],
        'jd' => ['code'=>'JD','name'=>'京东快递'],
        'shunfeng' => ['code'=>'SF','name'=>'顺丰快递'],
        'yuantong' => ['code'=>'YTO','name'=>'圆通快递'],
        'zhongtong' => ['code'=>'ZTO','name'=>'中通快递'],
        'yunda' => ['code'=>'YUNDA','name'=>'韵达快递'],
        'jtexpress' => ['code'=>'JITU','name'=>'极兔速递'],
        'youzhengguonei' => ['code'=>'POSTB','name'=>'邮政快递'],
        'ems' => ['code'=>'EMS','name'=>'EMS'],
    ];
    /**
     * 添加ressReachable
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function addressReachable($sdf) {
        $orderList = [];
        $primaryBn = '';
        foreach($sdf['orders'] as $v) {
            if(empty($primaryBn)) {
                $primaryBn = $v['order_bn'];
            }
            $orderList[] = ['order_id'=>$v['order_bn']];
        }
        $area = $sdf['branch']['area'];
        list(,$area,) = explode(':', $area);
        list($province, $city, $district) = explode('/', $area);
        $params = [
            'order_channel' => '1',
            'order_info_list' => json_encode($orderList),
            'sender_address' => json_encode([
                'province_name' => $province,
                'city_name' => $city,
                'district_name' => $district,
                'detail_address' => $sdf['branch']['address']
            ])
        ];
        $result = $this->__caller->call(SHOP_LOGISTICS_RECOMMENDED_DELIVERY,$params,null,'物流探查',10,$primaryBn);

        if ($result['rsp'] == 'succ') {
            $data = @json_decode($result['data'],true);
            $result['data'] = [];
            foreach($data['results']['data'][0]['express_info_list'] as $v) {
                $code = $this->logiMapCode[$v['express']] ? $this->logiMapCode[$v['express']]['code'] : $v['express'];
                $result['data'][$code] = [
                    'express' => $code,
                    'is_deliverable' => $v['is_deliverable'],
                    'is_shop_eBill' => $v['is_shop_eBill'],
                    'is_recommended' => $v['is_recommended'],
                    'avg_cost_hours' => $v['collect_sign_info']['avg_cost_hours'],
                    'level_percent' => $v['collect_sign_info']['level_percent'],
                ];
            }
        }

        return $result;
    }
}