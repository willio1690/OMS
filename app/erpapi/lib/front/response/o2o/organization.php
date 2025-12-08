<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *  前后端分离，门店组织类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:26:39+08:00
 */
class erpapi_front_response_o2o_organization extends erpapi_front_response_o2o_abstract
{
    /**
     * 门店组织结构，method=front.o2o.organization.listing
     *
     * @return void
     * @author
     **/

    public function listing($params)
    {
        self::trim($params);

        $this->__apilog['title']       = '门店组织结构查询';
        $this->__apilog['original_bn'] = $params['org_no'];

        $filter = array(
            'parent_id' => null,
            'org_no'    => $_SESSION['org_no'],
        );

        if ($params['org_no']) {
            // 注意：这里需要根据业务逻辑判断是否添加前缀
            // 如果是查询经销商，需要添加BS_前缀
            // 如果是查询门店或公司，不需要添加前缀
            // 由于API接口没有明确的org_type参数，暂时保持原样，后续可能需要优化
            $org = app::get('organization')->model('organization')->db_dump(array('org_no' => $params['org_no']), 'org_id');

            if (!$org) {
                $this->__apilog['result']['msg'] = '组织架构编码不存在';
                return false;
            }

            $filter['parent_id'] = $org['org_id'];
        }

        return $filter;
    }

    /**
     * 获取组织结构下的所有门店
     *
     * @return void
     * @author
     **/
    public function all($params)
    {
        $this->__apilog['title']       = '获取节点下的所有门店';
        $this->__apilog['original_bn'] = $params['org_no'];

        // if (!$params['org_no']) {
        //     $this->__apilog['result']['msg'] = '缺少组织架构编码';
        //     return false;
        // }

        $filter = array(
            'org_no'       => $params['org_no'],
            'branch_id'    => $_SESSION['branch_id'],
            'owner_org_no' => $_SESSION['org_no'],
        );

        return $filter;
    }
}
