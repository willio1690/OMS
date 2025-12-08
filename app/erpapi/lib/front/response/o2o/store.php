<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，门店类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_store extends erpapi_front_response_o2o_abstract
{
    /**
     * 门店列表查询，method=front.o2o.store.listing
     *
     * @return void
     * @author
     **/

    public function listing($params)
    {
        $this->__apilog['title'] = '门店列表查询';

        $filter = array(
            'status' => $params['status'] ? $params['status'] : '1',
        );
        return $filter;
    }

    /**
     * 门店详情查询，method=front.o2o.store.get
     *
     * @return void
     * @author
     **/
    public function get($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '门店详情查询';
        $this->__apilog['original_bn'] = $params['store_bn'];

        if (!$params['store_bn']) {
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }

        return $params;

    }
}
