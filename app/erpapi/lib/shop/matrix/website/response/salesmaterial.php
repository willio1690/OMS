<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc
 * @author:
 * @since:
 */
class erpapi_shop_matrix_website_response_salesmaterial extends erpapi_shop_response_salesmaterial {

    /**
     * 获取list
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function getlist($params)
    {

        $params = array_filter($params);
        $filter = array();

        $start_time = $params['start_time'] ? strtotime($params['start_time']) : 0;
        $end_time = $params['end_time'] ? strtotime($params['end_time']) : time();

        $filter = array(
            'last_modify|bthan' => $start_time,
            'last_modify|sthan' => $end_time,
        );

        if (isset($params['shop_bn'])) {
            $shopModel = app::get('ome')->model('shop');
            $shopInfo = $shopModel->dump(array('shop_bn' => $params['shop_bn']), 'shop_id');
            if (empty($shopInfo)) {
                $this->__apilog['result']['msg'] = '销售物料归属渠道不存在';
                return false;
            }
            $filter['shop_id'] = $params['shop_id'];
        }

        if (isset($params['sales_material_bn'])) {
            $filter['sales_material_bn'] = explode(',', $params['sales_material_bn']);
        }

        $filter['page_no'] = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $filter['page_size'] = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);

        return $filter;
    }
}
