<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单接口处理
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_shop_matrix_xiaomi_response_qianniu extends erpapi_shop_response_qianniu
{
    /**
     * ERP订单
     * 
     * @var string
     * */

    public $_order_detail= array();

    /**
     * 订单接收格式
     * 
     * @var string
     * */
    public $_qnordersdf = array();




        /**
     * 添加ress_modify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function address_modify($sdf)
    {

        $sdf['bizOrderId'] = $sdf['tid'];
        return parent::address_modify($sdf);
    }


    protected function _formatSdf(){
        if (is_string($this->_qnordersdf['modifiedAddress'])) {
            $this->_qnordersdf['modifiedAddress'] = json_decode($this->_qnordersdf['modifiedAddress'],true);
        }

        $modifiedAddress = $this->_qnordersdf['modifiedAddress'];

        if ($modifiedAddress['consignee'])           $this->_qnordersdf['consignee']['name']     = $modifiedAddress['consignee'];
        if ($modifiedAddress['province'])       $this->_qnordersdf['consignee']['province'] = $modifiedAddress['province']['name'];
        if ($modifiedAddress['city'])           $this->_qnordersdf['consignee']['city']     = $modifiedAddress['city']['name'];
        if ($modifiedAddress['area'])           $this->_qnordersdf['consignee']['area']     = $modifiedAddress['area']['name'];
        if ($modifiedAddress['address'])  $this->_qnordersdf['consignee']['addr']     = false !== strpos($modifiedAddress['address'], $modifiedAddress['area']['name']) ?$modifiedAddress['address'] : $modifiedAddress['area']['name'].$modifiedAddress['address'];
        if ($modifiedAddress['tel'])          $this->_qnordersdf['consignee']['mobile']   = $modifiedAddress['tel'];

    }

}
