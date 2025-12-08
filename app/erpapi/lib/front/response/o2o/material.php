<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，门店基础物料类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_material extends erpapi_front_response_o2o_abstract
{
    /**
     * 门店列表查询，method=front.o2o.material.listing
     *
     * @return void
     * @author
     **/

    public function listing($params)
    {
        $this->__apilog['title']       = '门店基础物料查询';
        $this->__apilog['original_bn'] = '';

        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }

        $limit = $params['page_size'] ? $params['page_size'] : MAX_LIMIT;

        $filter = array(
            'branch_id' => $_SESSION['branch_id'],
            'offset'    => ($page_no - 1) * $limit,
            'limit'     => $limit,
        );

        if ($params['bn']) {
            $material = app::get('material')->model('basic_material')->db_dump(array('material_bn' => $params['bn']), 'bm_id');

            $filter['bm_id'] = $material ? $material['bm_id'] : 0;
        }

        if ($params['store_bn']) {
            $orgMdl = app::get('organization')->model('organization');

            $store_list = $orgMdl->get_all_children($params['store_bn']);

            if (!$store_list) {
                $this->__apilog['result']['msg'] = '无门店节点';

                return false;
            }

            $store_bn   = array_column($store_list, 'org_no');
            $store_list = app::get('o2o')->model('store')->getList('branch_id', array('store_bn' => $store_bn));
            $branch_id  = array_column($store_list, 'branch_id');

            // 取交集
            $branch_id = array_intersect((array) $branch_id, (array) $filter['branch_id']);

            $filter['branch_id'] = $branch_id ? $branch_id : 0;
        }

        return $filter;
    }

    /**
     * 门店库存总计，method=front.o2o.material.count
     *
     * @return void
     * @author
     **/
    public function count($params)
    {
        $this->__apilog['title']       = '门店基础物料总计';
        $this->__apilog['original_bn'] = '';

        $filter = array(
            'branch_id' => $_SESSION['branch_id'],
        );

        if ($params['bn']) {
            $material = app::get('material')->model('basic_material')->db_dump(array('material_bn' => $params['bn']), 'bm_id');

            $filter['bm_id'] = $material ? $material['bm_id'] : 0;
        }

        return $filter;
    }

    /**
     * 管理员登陆，method=front.o2o.material.get
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
            'bn'        => trim($params['bn']),
            'branch_id' => $_SESSION['branch_id'],
        );

        if ($params['store_bn']) {
            $store = app::get('o2o')->model('store')->db_dump(array('store_bn' => $params['store_bn']), 'branch_id');
            if (!$store) {
                $this->__apilog['result']['msg'] = '门店不存在';

                return false;
            }

            $filter['branch_id'] = $store['branch_id'];
        }

        return $filter;
    }

}
