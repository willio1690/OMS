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
class erpapi_wms_request_branch extends erpapi_wms_request_abstract
{
    /**
     * 获取仓库列表
     *
     * @return void
     * @author
     **/

    public function branch_getlist($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '获取仓库列表';

        return $this->__caller->call(WMS_WAREHOUSE_LIST_GET, null, null, $title, 10);
    }

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
        if ($rsp['data']['data']) {
            foreach ($rsp['data']['data'] as $v) {
                $data[] = array(

                    'shop_type'      => '360buy',
                    'outregion_id'   => $v['id'],
                    'outregion_name' => $v['name'],
                    'region_grade'   => $sdf['region_grade'],
                    'outparent_id'   => $v['parentId'],
                );
            }
        }

        $result['data'] = $data;
        return $result;
    }

    /**
     * 获取外部仓ID
     *
     * @param array $sdf，示例:['ship_province':'','ship_city':'','ship_district':'','ship_town':'','ship_village':'','ship_addr':'']
     * @return void
     * @author
     **/
    public function branch_getAreaId($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '获取地址外部ID';

        $params = [
            'province' => $sdf['ship_province'],
            'city'     => $sdf['ship_city'],
            'street'   => $sdf['ship_district'],
            'town'     => $sdf['ship_town'],
            'address'  => $sdf['ship_addr'],
        ];

        $regionMdl       = app::get('eccommon')->model('platform_regions');
        $local_path_name = implode('-', array_filter([$params['province'], $params['city'], $params['street'], $params['town']]));
        $localInfo       = $regionMdl->db_dump(['local_path_name' => $local_path_name, 'shop_type' => '360buy']);

        if ($localInfo && $localInfo['out_path_id']) {
            $result['rsp']             = 'succ';
            $result['data']['address'] = $params['address'];

            list($result['data']['provinceid'], $result['data']['cityid'], $result['data']['streetid'], $result['data']['townid']) = explode('-', $localInfo['out_path_id']);

            return $result;
        }

        $result = $this->__caller->call(WMS_AREA_ADDRESS_EXCHANGE, $params, null, $title, 10, strval($sdf['original_bn']));

        $data = @json_decode($result['data'], true);

        $result['data'] = [];
        if ($result['rsp'] == 'succ' && $data['data']) {
            // data:{[cityid] => 2813,[provinceid] => 2,[townid] => 0,[streetid] => 61129,[address] => 宜山路700}
            $result['data'] = $d = $data['data'];

            if ($params['town']) {
                // 四级地址
                $regionInfo = [
                    'outregion_id'      => $d['townid'],
                    'outregion_name'    => $params['town'],
                    'outparent_id'      => $d['streetid'],
                    'outparent_name'    => $params['street'],
                    'out_path_name'     => '',
                    'out_path_id'       => sprintf('%s-%s-%s-%s', $d['provinceid'], $d['cityid'], $d['streetid'], $d['townid']),
                    'region_grade'      => 4,
                    'shop_type'         => '360buy',
                    'local_region_id'   => 0,
                    'local_region_name' => $params['town'],
                    'local_path_name'   => sprintf('%s-%s-%s-%s', $params['province'], $params['city'], $params['street'], $params['town']),
                    'mapping'           => $d['townid'] ? 1 : 0,
                ];
            } elseif ($params['street']) {
                // 三级地址
                $regionInfo = [
                    'outregion_id'      => $d['streetid'],
                    'outregion_name'    => $params['street'],
                    'outparent_id'      => $d['cityid'],
                    'outparent_name'    => $params['city'],
                    'out_path_name'     => '',
                    'out_path_id'       => sprintf('%s-%s-%s', $d['provinceid'], $d['cityid'], $d['streetid']),
                    'region_grade'      => 3,
                    'shop_type'         => '360buy',
                    'local_region_id'   => 0,
                    'local_region_name' => $params['street'],
                    'local_path_name'   => sprintf('%s-%s-%s', $params['province'], $params['city'], $params['street']),
                    'mapping'           => $d['streetid'] ? 1 : 0,
                ];
            } else if ($params['city']) {
                // 二级地址
                $regionInfo = [
                    'outregion_id'      => $d['cityid'],
                    'outregion_name'    => $params['city'],
                    'outparent_id'      => $d['provinceid'],
                    'outparent_name'    => $params['province'],
                    'out_path_name'     => '',
                    'out_path_id'       => sprintf('%s-%s', $d['provinceid'], $d['cityid']),
                    'region_grade'      => 2,
                    'shop_type'         => '360buy',
                    'local_region_id'   => 0,
                    'local_region_name' => $params['province'],
                    'local_path_name'   => sprintf('%s-%s', $params['province'], $params['city']),
                    'mapping'           => $d['cityid'] ? 1 : 0,
                ];
            }

            if ($regionInfo) {
                $regionMdl->insert($regionInfo);
            }
        }

        return $result;
    }
}
