<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，基础物料类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_material extends erpapi_front_response_abstract
{
    /**
     * 管理员登陆，method=front.material.get
     *
     * @author
     **/

    public function get($params)
    {
        $this->__apilog['title']       = '基础物料详情查询';
        $this->__apilog['original_bn'] = $params['bn'];

        if (!$params['bn']) {
            $this->__apilog['result']['msg'] = '缺少基础物料编码';
            return false;
        }

        $filter = array(
            'bn' => trim($params['bn']),
        );

        return $filter;
    }
}
