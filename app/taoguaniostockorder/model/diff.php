<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoguaniostockorder_mdl_diff extends dbeav_model
{
    public $diff_status = array(
        1 => '未处理',
        2 => '部分处理',
        3 => '全部处理',
        4 => '取消',
    );
    /**
     * 差异单审核
     * @param $data
     * @param $err_msg
     * @return bool
     */
    public function stockAdjustment($data, &$err_msg)
    {
        if ($data['check_status'] == '2') {
            $err_msg = '单据已经审核，不能再审核';
            return false;
        }
        $groupItems = array();
        foreach ($data['items'] as $k => $v) {
            $key                = $v['diff_reason'] . '_' . $v['responsible'];
            $groupItems[$key][] = $v;
        }
        unset($data['items']);//删除原明细数据
        $success = true;
        $errArr  = [];
        foreach ($groupItems as $k => $items) {
            list($shift_data, $rule) = $this->diffParameter($data, $items);
            $diffProcessLib = kernel::single('taoguaniostockorder_diff_process', $rule);
            list($status, $errmsg) = $diffProcessLib->rulerule($shift_data);
            //循环结束在做返回
            if (!$status) {
                $success  = false;
                $errArr[] = $errmsg;
            }
        }
        if (!$success) {
            $err_msg = implode(';', $errArr);
            return false;
        }
        //最后处理主表状态
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $res     = $diffMdl->update(array('diff_status' => '3', 'check_status' => '2'), array('diff_id' => $data['diff_id']));
        if (!$res) {
            $err_msg = '审核失败';
            return false;
        }
        return true;
        
    }
    
    /**
     * 责任数据整合
     * @param $data
     * @param $items
     * @return array[]
     */
    public function diffParameter($data, $items)
    {
        $branchMdl  = app::get('ome')->model('branch');
        $shift_data = array(
            'items'   => $items,
            'diff_bn' => $data['diff_bn'],
            'diff_id' => $data['diff_id'],
        );
        
        $branch_id      = $data['branch_id'];
        $extrabranch_id = $data['extrabranch_id'];
        
        $reason      = $items[0]['diff_reason'];
        $responsible = $items[0]['responsible'];
        //判断责任方仓库
        $save_branch_id = '';
        if ($responsible == '2') {
            //发货方责任
            $save_branch_id = $extrabranch_id;
        } elseif ($responsible == '3') {
            //收货方责任
            $save_branch_id = $branch_id;
        } elseif ($responsible == '4') {
            //物流方责任
            $save_branch_id = $this->getLogisticsShop()['branch_id'];
        }
        $shift_data['branch_id'] = $save_branch_id;
        
        //根据type_id 判断责任方 是仓库 还是店铺
        $branchInfo       = $branchMdl->db_dump(array('branch_id' => $save_branch_id, 'check_permission' => 'false'), 'b_type');
        $responsibleValue = '';
        if ($branchInfo['b_type'] == '1') {
            $responsibleValue = 'branch';
        } elseif (in_array($branchInfo['b_type'], ['2', '3'])) {
            $responsibleValue = 'store';
        } elseif ($responsible == '4') {
            $responsibleValue = 'logistics';
        }
        
        //获取流程 判断流程
        $flow            = '';
        $branchInfo      = $branchMdl->db_dump(array('branch_id' => $branch_id, 'check_permission' => 'false'), 'branch_id,b_type');
        $extrabranchInfo = $branchMdl->db_dump(array('branch_id' => $extrabranch_id, 'check_permission' => 'false'), 'branch_id,b_type');
        
        if ($branchInfo['b_type'] == '1' && in_array($extrabranchInfo['b_type'], ['2', '3'])) {
            //仓到店
            $flow = 'b2s';
        } elseif (in_array($branchInfo['b_type'], ['2', '3']) && $extrabranchInfo['b_type'] == '1') {
            //店到仓
            $flow = 's2b';
        } elseif (in_array($branchInfo['b_type'], ['2', '3']) && in_array($extrabranchInfo['b_type'], ['2', '3'])) {
            //店转店
            $flow = 's2s';
        } elseif (in_array($branchInfo['b_type'], ['1']) && in_array($extrabranchInfo['b_type'], ['1'])) {
            //店转店
            $flow = 'b2b';
        }
        
        $rule = array('flow' => $flow, 'reason' => $reason, 'responsible' => $responsibleValue);
        return [$shift_data, $rule];
    }
    
    /**
     * 获取LogisticsShop
     * @return mixed 返回结果
     */
    public function getLogisticsShop()
    {
        //需要新增一个物流方责任仓
        return app::get('ome')->model('branch')->db_dump(['branch_bn' => '0', 'check_permission' => 'false']);
    }
}