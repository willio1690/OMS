<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_shop{

    /**
     * 添加
     * @param mixed $data 数据
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($data, &$code, &$sub_msg)
    {
        $shop_bn = $data['shop_bn'];

        $shopMdl = app::get('ome')->model('shop');

        if ($shopMdl->count(array('shop_bn'=>$shop_bn))) {
            return ['rsp' => 'fail', 'msg' => '店铺已存在'];
        }

        if ($data['s_type'] && !in_array($data['s_type'], ['1', '2'])) {
            return ['rsp' => 'fail', 'msg' => 's_type可选值为1或2'];
        }

        if ($data['business_category'] && !in_array($data['business_category'], ['B2C', 'B2B'])) {
            return ['rsp' => 'fail', 'msg' => 'business_category可选值为B2C或B2B'];
        }

        $area = '';
        if($data['province']){
            $area = $data['province'].'/'.$data['city'].'/'.$data['district'];
            kernel::single('eccommon_regions')->region_validate($area);
        }

        $shopData = [
            'shop_id' => md5($data['shop_bn']),
            'shop_bn' => $data['shop_bn'],
            'name' => $data['shop_name'],
            'addr' => $data['addr'],
            'mobile' => $data['mobile'],
            'area' => $area,
            'tel' => $data['tel'],
            'zip' => $data['zip'],
            'source' => 'openapi',
        ];

        if ($data['s_type']) {
            $shopData['s_type'] = $data['s_type'];
        }

        if ($data['business_category']) {
            $shopData['business_category'] = $data['business_category'];
        }

        if (!$shopMdl->insert($shopData)) {
            return ['rsp' => 'fail', 'msg' => '添加失败'];
        }

        return ['rsp' => 'succ', 'msg' => '添加成功', 'data' => ['shop_bn' => $shop_bn]];
    }

    /**
     * edit
     * @param mixed $data 数据
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function edit($data, &$code, &$sub_msg)
    {
        return ['rsp' => 'succ', 'msg' => '编辑成功'];
    }

    /**
     * 获取List
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getList($data)
    {
    }
    
}
