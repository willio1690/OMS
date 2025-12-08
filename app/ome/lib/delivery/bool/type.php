<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing@shopex.cn
 * @describe 发货单布尔型标识
 */

class ome_delivery_bool_type {
  
    #平台自发货
    const __PLATFORM_CODE   = 0x002;
    
    //试用
    const __TRY_CODE = 0x004;
    
    //翱象订单
    const __AOXIANG_CODE = 0x008;

    #DEWU急速现货
    const __JISU_CODE    = 0x040;
    
    //送货上门
    const __SHSM_CODE = 0x0010;
    #天猫物流升级
    const __CPUP_CODE       = 0x00020;
    const __JDLVMI_CODE = 0x8000;
    private $boolStatus = array(
        self::__PLATFORM_CODE => array('identifier'=>'平', 'text'=>'平台自发订单', 'color'=>'#0F8A5F'),
        self::__CPUP_CODE => array('identifier'=>'TM物', 'text'=>'天猫物流升级', 'color'=>'#fecc66'),
        self::__AOXIANG_CODE => array('identifier'=>'翱象', 'text'=>'翱象订单', 'color'=>'yellow'),
        self::__JISU_CODE           => array('identifier' => '急速现货', 'text' => 'DEWU急速现货', 'color' => '#C481A5'),
        self::__SHSM_CODE => array('identifier'=>'送', 'text'=>'送货上门', 'color'=>'LightYellow'),
        self::__JDLVMI_CODE => array('identifier'=>'JDLVMI', 'text'=>'京东云仓', 'color'=>'LimeGreen', 'search'=>'false'),

    );

    public function getBoolTypeText($num = null) {
        if($num) {
            return (array) $this->boolStatus[$num];
        }
        return $this->boolStatus;
    }

    public function getBoolTypeIdentifier($boolType,$shop_type = 'taobao') {
        $str = '';
        foreach ($this->boolStatus as $k => $val) {

            if ($boolType & $k) {
                if($shop_type == 'luban' && $boolType == self::__CPUP_CODE){
                    $val['identifier'] = 'DY物';
                    $val['text'] = 'DY物流升级';
                    $val['color'] = '#FF8800 ';
                }

                $str .= sprintf("<span class='tag-label' title='%s' style='background-color:%s;color:#000000;'>%s</span>", $val['text'], $val['color'], $val['identifier']);
            }
        }
        return $str;
    }

    public function getBoolType($filter) {
        $where = array();
        if($filter['in']) {
            $in = 0;
            foreach((array)$filter['in'] as $val) {
                $in = $in | $val;
            }
            $where[] = 'bool_type & ' . $in . ' = ' . $in;
        }
        if($filter['out']) {
            $out = 0;
            foreach((array)$filter['out'] as $val) {
                $out = $out | $val;
            }
            $where[] = '!(bool_type & ' . $out . ')';
        }
        if(empty($where)) {
            return array();
        }
        $sql = 'select bool_type from sdb_ome_delivery where ' . implode(' and ', $where) . ' group by bool_type';
        $boolData = kernel::database()->select($sql);
        $boolStatus = array('-1');
        foreach($boolData as $val) {
            $boolStatus[] = $val['bool_type'];
        }
        return $boolStatus;
    }
    
    public function isTry($boolType)
    {
        return $boolType & self::__TRY_CODE ? true : false;
    }
    
    public function isAoxiang($boolType)
    {
        return $boolType & self::__AOXIANG_CODE ? true : false;
    }
    
    public function isCPUP($boolType)
    {
        return $boolType & self::__CPUP_CODE ? true : false;
    }

    public function isJISU($boolType)
    {
        return $boolType & self::__JISU_CODE ? true : false;
    }

}
