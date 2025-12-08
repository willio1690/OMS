<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 华为商城平台对接
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_huawei_request_product extends erpapi_shop_request_product
{
    //根据IID获取单个商品
    /**
     * item_get
     * @param mixed $iid ID
     * @return mixed 返回值
     */

    public function item_get($iid)
    {
        $title = '单拉商品[' . $iid . ']';
        
        $params = array(
            'product_id' => $iid,
        );
        
        //失败重试3次
        for ($i=0; $i<3; $i++)
        {
            $result = $this->__caller->call(SHOP_ITEM_GET, $params, array(), $title, 20, $iid);
            if ($result['rsp'] == 'succ') break;
        }
        
        //empty
        if ($result['rsp'] != 'succ' || empty($result['data'])){
            return array();
        }
        
        //json_decode
        if ($result['data']){
            $result['data'] = @json_decode($result['data'],true);
        }
        
        return $result;
    }
}
