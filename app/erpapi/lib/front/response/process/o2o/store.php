<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店处理类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:56:04+08:00
 */
class erpapi_front_response_process_o2o_store
{
    /**
     * 门店列表查询
     *
     * @return void
     * @author
     **/

    public function listing($data)
    {
        $filter = array(
        );

        if (isset($data['status'])) {
            $filter['status'] = $data['status'];
        }

        $store_list = app::get('o2o')->model('store')->getList('*', $filter);

        $rData = array('stores' => array());
        foreach ($store_list as $value) {
            $rData['stores'][] = array(
                'store_bn'   => $value['store_bn'],
                'store_name' => $value['name'],
                'status'     => $value['status'],
            );
        }

        return array('rsp' => 'succ', 'data' => $rData);
    }

    /**
     * 门店详情查询
     *
     * @return void
     * @author
     **/
    public function get($data)
    {
        $filter = array(
            'store_bn' => $data['store_bn'],
        );

        $store = app::get('o2o')->model('store')->db_dump($filter);

        $rData['store'] = array();
        if ($store) {
            $rData['store']['store_bn']   = $store['store_bn'];
            $rData['store']['store_name'] = $store['name'];
            $rData['store']['status']     = $store['status'];
            $rData['store']['addr']       = $store['addr'];
            $rData['store']['zip']        = $store['zip'];
            $rData['store']['contacter']  = $store['contacter'];
            $rData['store']['mobile']     = $store['mobile'];
            $rData['store']['tel']        = $store['tel'];

            list(, $area)                     = explode(':', $store['area']);
            list($province, $city, $district) = explode('/', $area);

            $rData['store']['province'] = $province;
            $rData['store']['city']     = $city;
            $rData['store']['district'] = $district;
        }

        return array('rsp' => 'succ', 'data' => $rData);
    }
}
