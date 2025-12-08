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
class erpapi_front_response_process_o2o_organization
{
    /**
     * 门店列表查询
     *
     * @return void
     * @author
     **/

    public function listing($filter)
    {
        if (!$filter['parent_id']) {
            $filter['filter_sql'] = ' parent_id is NULL ';
            unset($filter['parent_id']);
        }

        $org_list = app::get('organization')->model('organization')->getList('org_no,org_name,org_type,org_level_num,parent_id', $filter);

        $data = array(
            'orgs' => $org_list,
        );

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 获取组织结构下的所有门店
     *
     * @return void
     * @author
     **/
    public function all($filter)
    {
        $orgMdl = app::get('organization')->model('organization');

        if ($filter['org_no']) {
            $store_list = $orgMdl->get_all_children($filter['org_no']);

            $my_stores = app::get('o2o')->model('store')->getList('store_bn', array('branch_id' => $filter['branch_id']));
            $my_stores = array_column($my_stores, 'store_bn');
            foreach ($store_list as $key => $value) {
                if (!in_array($value['org_no'], $my_stores)) {
                    unset($store_list[$key]);
                }
            }

            $store_list = array_values($store_list);
        } else {

            $store_list = $orgMdl->getList('org_id,org_no,org_name,org_type,parent_id,haschild', array('org_type' => '2', 'status' => '1'));
        }

        return array('rsp' => 'succ', 'data' => array('stores' => $store_list));
    }
}
