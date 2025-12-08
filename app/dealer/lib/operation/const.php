<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 异常标识常量定义Lib方法类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.26
 */
class dealer_operation_const
{
    //取消经销商订单失败
    const __CANCEL_DEALER_ORDER = 0x001;
    
    //取消OMS订单失败
    const __CANCEL_OMS_ORDER = 0x002;
    
    //编辑修改订单失败
    const __EDIT_DEALER_ORDER =  0x004;
    
    //暂停订单失败
    const __PAUSE_DEALER_ORDER = 0x008;
    
//    //物流公司标记
//    const __LOGI_CODE =0x010;
    
    //异常标识列表
    private $boolStatus = array(
        self::__CANCEL_DEALER_ORDER => array('identifier'=>'消', 'text'=>'取消经销商订单失败', 'color'=>'yellow'),
        self::__CANCEL_OMS_ORDER => array('identifier'=>'废', 'text'=>'作废OMS订单失败', 'color'=>'red'),
        self::__EDIT_DEALER_ORDER => array('identifier'=>'修', 'text'=>'编辑修改订单失败', 'color'=>'blue'),
        self::__PAUSE_DEALER_ORDER => array('identifier'=>'停', 'text'=>'暂停订单失败', 'color'=>'LimeGreen'),
    );
    
    /**
     * 获取异常标识
     * 
     * @param $boolType
     * @return string
     */

    public function getAbnormalTag($boolType)
    {
        if(empty($boolType)){
            return '';
        }
        
        $str = '';
        foreach ($this->boolStatus as $boolKey => $val)
        {
            if($boolType & $boolKey){
                $str .= sprintf("<span class='tag-label' title='%s' style='background-color:%s;color:#000000;'>%s</span>", $val['text'], $val['color'], $val['identifier']);
            }
        }
        
        return $str;
    }
}