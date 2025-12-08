<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 天猫订阅消息请求接口函数实现类
 * wangjianjun 20181107
 * @version 0.1
 */
class erpapi_tmcgroup_request_tmcgroup extends erpapi_invoice_request_abstract{

    /**
     * 电子发票订阅消息添加分组
     * @param array $sdf 请求参数
     */
    public function bind_tbtmcgroup($params){
        $sdf = array(
            'group_name' => 'einvoice'
        );
        $title = '店铺（' . $this->__channelObj->tmcgroup['name'] .'）电子发票添加分组';
        $result = $this->__caller->call(EINVOICE_ADD_TMC_GROUP,$sdf,null,$title,10);
        if ($result['rsp'] == 'succ') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 电子发票订阅消息删除分组（没有启用）
     * @param array $sdf 请求参数
     */
    public function unbind_tbtmcgroup($params){
        return false;
        //由于电子面单线无此发起接口先注释掉
//         $sdf = array(
//             'group_name' => 'einvoice'
//         );
//         $title = '店铺（' . $this->__channelObj->tmcgroup['name'] .'）电子发票删除分组';
//         $result = $this->__caller->call(EINVOICE_DEL_TMC_GROUP,$sdf,null,$title,10);
//         if ($result['rsp'] == 'succ') {
//             return true;
//         } else {
//             return false;
//         }
    }
    
}