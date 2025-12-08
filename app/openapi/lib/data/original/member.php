<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_member{

    /**
     * 添加
     * @param mixed $data 数据
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($data, &$code, &$sub_msg)
    {
        $uname = $data['uname'];

        $memberMdl = app::get('ome')->model('members');
        $shopMdl = app::get('ome')->model('shop');

        if ($memberMdl->count(array('uname'=>$uname))) {
            return ['rsp' => 'fail', 'msg' => '会员名已存在'];
        }

        $area = '';
        if($data['province']){
            $area = $data['province'].'/'.$data['city'].'/'.$data['district'];
            kernel::single('eccommon_regions')->region_validate($area);
        }

        $shop_bn = $data['shop_bn'];
        $shop = $shopMdl->db_dump(['shop_bn'=>$shop_bn]);
        if(!$shop){
            return ['rsp' => 'fail', 'msg' => '店铺不存在'];
        }

        $memberData = [
            'shop_id' => $shop['shop_id'],
            'shop_type' => $shop['shop_type'],
            'uname' => $data['uname'],
            'uname_md5' => md5($data['uname'].$data['buyer_open_uid']),
            'name' => $data['name'],
            'buyer_open_uid' => $data['buyer_open_uid'],
            'addr' => $data['addr'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'area' => $area,
            'tel' => $data['tel'],
            'zip' => $data['zip'],
            'ship_tax' => $data['ship_tax'],
            'remark' => $data['remark'],
            'source' => 'openapi',
        ];

        if (in_array($data['sex'], ['female', 'male'])) {
            $memberData['sex'] = $data['sex'];
        }

        if (!$memberMdl->insert($memberData)) {
            return ['rsp' => 'fail', 'msg' => '添加失败'];
        }

        return ['rsp' => 'succ', 'msg' => '添加成功', 'data' => ['uname' => $uname]];
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
        $uname = $data['uname'];

        $memberMdl = app::get('ome')->model('members');

        $member = $memberMdl->db_dump(['uname'=>$uname], 'member_id');
        if (!$member) {
            return ['rsp' => 'fail', 'msg' => '会员不存在'];
        }

        $area = '';
        if($data['province']){
            $area = $data['province'].'/'.$data['city'].'/'.$data['district'];
            kernel::single('eccommon_regions')->region_validate($area);
        }

        $memberData = [
            'name' => $data['name'],
            'addr' => $data['addr'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'area' => $area,
            'tel' => $data['tel'],
            'zip' => $data['zip'],
            'ship_tax' => $data['ship_tax'],
            'remark' => $data['remark'],
        ];

        if (in_array($data['sex'], ['female', 'male'])) {
            $memberData['sex'] = $data['sex'];
        }

        if (!$memberMdl->update($memberData, ['member_id'=>$member['member_id']])) {
            return ['rsp' => 'fail', 'msg' => '编辑失败'];
        }

        return ['rsp' => 'succ', 'msg' => '编辑成功', 'data' => ['uname' => $uname]];
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
