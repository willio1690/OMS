<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/9/19 15:09:41
 * @describe: 类
 * ============================
 */
class ome_refund_flag {
    private $flag = [
        1 => '拦截包裹',
        2 => '协商退货退款',
    ];

    /**
     * 更新
     * @param mixed $applyId ID
     * @param mixed $flagTxt flagTxt
     * @return mixed 返回值
     */

    public function update($applyId, $flagTxt) {
        if(empty($applyId)) {
            return [false, '参数不全'];
        }
        $mdlApply = app::get('ome')->model('refund_apply');
        $index = array_search($flagTxt, $this->flag);
        if($index === false) {
            return [false, '没有该标识'];
        }
        $row = $mdlApply->db_dump($applyId, 'flag');
        $flagArr = explode(',', $row['flag']);
        if(in_array($index, $flagArr)) {
            return [true, '已经存在'];
        }
        $mdlApply->update(['flag'=>$row['flag'].','.$index], ['apply_id'=>$applyId]);
        return [true, '更新成功'];
    }

    /**
     * 获取FlagTxt
     * @param mixed $flag flag
     * @return mixed 返回结果
     */
    public function getFlagTxt($flag) {
        $flagArr = explode(',', $flag);
        $return = [];
        foreach ($flagArr as $v) {
            if($this->flag[$v]) $return[] = $this->flag[$v];
        }
        return implode(',', $return);
    }
}