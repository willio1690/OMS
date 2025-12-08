<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_dewu_request_logistics extends erpapi_shop_request_logistics
{

    // 查询卖家发货地址 (获取商家品牌直发发货地址信息，如果订单为品牌直发多仓模式时，接单时需要传入地址id)
    /**
     * 搜索Address
     * @param mixed $search_type search_type
     * @param mixed $page page
     * @return mixed 返回值
     */
    public function searchAddress($search_type = '', $page = 0)
    {
        //请求参数
        $params = [
            'page_no'   => $page, // 页码,默认为1
            'page_size' => 30, // 每页数量，默认20页，每页最多展示30条
        ];

        $shop_id   = $this->__channelObj->channel['shop_id'];
        $shop_bn   = $this->__channelObj->channel['shop_bn'];
        $shop_type = $this->__channelObj->channel['shop_type'];

        $title    = '店铺(' . $this->__channelObj->channel['name'] . ')获取卖家发货地址';
        $callback = [];

        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_QUERY_SELLER_ADDRESS, $params, $callback, $title, 10, $this->__channelObj->channel['shop_id']);

        if ($result['rsp'] != 'succ') {
            return $result;
        }

        //保存至本地
        $dataList     = json_decode($result['data'], true);
        $address_list = $dataList['list'];
        if ($address_list) {
            $oAddress = app::get('ome')->model('return_address');

            //[拉取第一页时]删除该店铺下的所有平台地址
            if ($page == 1) {
                $oAddress->delete(array('shop_id' => $shop_id, 'reship_id' => 0));
            }

            //list
            foreach ($address_list as $key => $val) {
                //check
                if (empty($val['address_id']) || !$val['warehouse_code']) {
                    continue;
                }
                // 地址配置状态，0：配置中，1：配置完成，2：配置失败。0、1或为null时可用
                if ($val['status'] == '2') {
                    continue;
                }

                $is_default = $val['is_default'] == '1' ? 'true' : 'false';

                //data
                $data = array(
                    // 'cancel_def'           => $is_default, //是否默认退货地址
                    'get_def'              => $is_default, //是否默认取货地址
                    'area_id'              => 0, //区域ID
                    'contact_id'           => $val['address_id'], //地址库ID
                    'shop_type'            => $shop_type,
                    'shop_id'              => $shop_id,
                    'province'             => $val['province'], //省
                    'city'                 => $val['city'], //市
                    'country'              => $val['district'], //区
                    'addr'                 => $val['detail_address'], //详细地址
                    'mobile_phone'         => $val['mobile'], //手机号码
                    'contact_name'         => $val['contact_name'], //联系人姓名
                    'branch_bn'            => $val['warehouse_code'],
                    'branch_name'          => $val['warehouse_name'],
                    'street'               => '', //街道
                    'zip_code'             => '', //地区邮政编码
                    'phone'                => '', //电话号码
                    'seller_company'       => '', //公司名称
                    'platform_create_time' => '',
                    'platform_update_time' => '',
                    'modify_date'          => time(),
                );
                $saveRs = $oAddress->save($data);
            }
        }

        $result['data'] = json_encode([
            'total'        => $dataList['total_results'],
            'address_list' => $dataList['list'],
            'page_no'      => $params['page_no'],
            'page_size'    => $params['page_size'],
        ]);
        return $result;
    }

}
