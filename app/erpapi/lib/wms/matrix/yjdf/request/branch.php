<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓库
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_yjdf_request_branch extends erpapi_wms_request_branch
{

    /**
     * branch_getAreaList
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function branch_getAreaList($sdf)
    {

        $title  = $this->__channelObj->wms['channel_name'] . '获取地址库列表';
        $params = array(
            'warehouse_code' => '123456',
            'parent_id'      => $sdf['parent_id'],

        );

        $rsp = $this->__caller->call(WMS_AREA_ADDRESS_GET, $params, null, $title, 10);

        $result            = array();
        $result['rsp']     = $rsp['rsp'];
        $result['err_msg'] = $rsp['err_msg'];
        $result['msg_id']  = $rsp['msg_id'];
        $result['res']     = $rsp['res'];
        $rsp['data']       = json_decode($rsp['data'], 1);
        $data              = array();
        if (is_array($rsp['data']) && is_array($rsp['data']['data'])) {
            foreach ($rsp['data']['data'] as $v) {
                $data[] = array(
                    'shop_type'      => '360buy',
                    'outregion_id'   => $v['id'],
                    'outregion_name' => $v['name'],
                    'region_grade' =>isset($sdf['region_grade']) ? $sdf['region_grade'] : 1,
                );
            }
        }

        $result['data'] = $data;
        return $result;
    }
}
