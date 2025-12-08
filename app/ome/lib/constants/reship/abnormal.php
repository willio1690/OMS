<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退货单异常标识
 */
class ome_constants_reship_abnormal
{
    const __GIFT_CODE = 0x001; //有赠品部分退货异常
    const __CONFIRM_CODE = 0x002; //京东云交易返回异常
    const __EQUAL_CODE = 0x004; //主品与赠品相同
    const __LOGISTICS_CODE = 0x008; //同步WMS物流信息失败
    const __CREATE_CODE = 0x010; //创建退货服务单失败
    const __RESULT_CODE = 0x020; //审核结果不一致
    const __AGREE_CODE = 0x040; //平台同意售后单失败
    const __EXCHANGE_DIFF_CODE = 0x080; //换货商品与退货商品不相同
    const __TRANSFORM_RETURN_CODE = 0x100; //抖音退货、换货、售后仅退款单据转换
    const __ERVICE_AUDIT_CODE = 0x200; //京东服务单审核不通过
    const __ADDRESS_FAIL_CODE = 0x400; //京东寄件地址解析失败
    
    private $status = array(
        self::__GIFT_CODE => array('identifier'=>'赠', 'text'=>'赠品订单,不可部分退货', 'color'=>'red', 'search'=>'true'),
        self::__CONFIRM_CODE => array('identifier'=>'退', 'text'=>'云交易商品不可售后', 'color'=>'orange', 'search'=>'true'),
        self::__EQUAL_CODE => array('identifier'=>'同', 'text'=>'主品与赠品相同,不支持退货', 'color'=>'#F38D23', 'search'=>'true'),
        self::__LOGISTICS_CODE => array('identifier'=>'物', 'text'=>'同步物流信息失败', 'color'=>'#BD794E', 'search'=>'true'),
        self::__CREATE_CODE => array('identifier'=>'创', 'text'=>'申请售后服务单失败', 'color'=>'#44607B', 'search'=>'true'),
        self::__RESULT_CODE => array('identifier'=>'果', 'text'=>'审核结果不一致', 'color'=>'#F38D23', 'search'=>'true'),
        self::__AGREE_CODE => array('identifier'=>'审', 'text'=>'平台同意售后单失败', 'color'=>'#FF6633', 'search'=>'true'),
        self::__EXCHANGE_DIFF_CODE => array('identifier'=>'换', 'text'=>'换货与退货商品不同', 'color'=>'yellow', 'search'=>'true'),
        self::__TRANSFORM_RETURN_CODE => array('identifier'=>'转', 'text'=>'转换售后申请单失败', 'color'=>'#FF00FF', 'search'=>'true'),
        self::__ERVICE_AUDIT_CODE => array('identifier'=>'核', 'text'=>'京东服务单审核不通过', 'color'=>'#6655ff', 'search'=>'true'),
        self::__ADDRESS_FAIL_CODE => array('identifier'=>'寄', 'text'=>'京东寄件地址解析失败', 'color'=>'red', 'search'=>'true'),
    );
    
    /**
     * 获取Text
     * @param mixed $key key
     * @return mixed 返回结果
     */

    public function getText($key = null)
    {
        if ($key) {
            return (array) $this->status[$key];
        }
        
        return $this->status;
    }
    
    /**
     * 获取Identifier
     * @param mixed $s s
     * @return mixed 返回结果
     */
    public function getIdentifier($s)
    {
        $str = '';
        
        foreach ($this->status as $k => $v) {
            if ($s & $k) {
                $str .= sprintf("<span class='tag-label' title='%s' style='background-color:%s;color:#000000;'>%s</span>", $v['text'], $v['color'], $v['identifier']);
            }
        }
        
        return $str;
    }
    
    /**
     * 获取BoolType
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getBoolType($filter)
    {
        $where = array();
        
        if ($filter['in']) {
            $in = 0;
            foreach ((array) $filter['in'] as $val) {
                $in = $in | $val;
            }
            $where[] = 'abnormal_status & ' . $in . ' = ' . $in;
        }
        
        if ($filter['out']) {
            $out = 0;
            foreach ((array) $filter['out'] as $val) {
                $out = $out | $val;
            }
            $where[] = '!(abnormal_status & ' . $out . ')';
        }
        
        if (empty($where)) {
            return array();
        }
        
        $sql  = 'select distinct abnormal_status from sdb_ome_reship where ' . implode(' and ', $where);
        $rows = kernel::database()->select($sql);
        
        return $rows ? array_column($rows, 'abnormal_status') : ['-1'];
    }
    
    /**
     * 获取Options
     * @return mixed 返回结果
     */
    public function getOptions()
    {
        $options = array();
        foreach ($this->status as $k => $v) {
            if ($v['search'] == 'true') {
                $options[$k] = $v['text'];
            }
        }
        
        return $options;
    }
}
