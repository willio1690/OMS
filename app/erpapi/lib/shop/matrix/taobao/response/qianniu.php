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
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_taobao_response_qianniu extends erpapi_shop_response_qianniu
{
    /**
     * ERP订单
     *
     * @var string
     **/
    public $_order_detail= array();

    /**
     * 订单接收格式
     *
     * @var string
     **/
    public $_qnordersdf = array();


    protected function _formatSdf(){

        if (is_string($this->_qnordersdf['modifiedAddress'])) {

            $this->_qnordersdf['modifiedAddress'] = json_decode($this->_qnordersdf['modifiedAddress'],true);

            if (isset($this->_qnordersdf['oaid']) && !empty($this->_qnordersdf['oaid'])) {
                // 加密字段处理
                $hashCode = kernel::single('ome_security_hash')->get_code();
                foreach ($this->_qnordersdf['modifiedAddress'] as $key => $value) {
                    if(strpos($value, '*') !== false) {
                        $this->_qnordersdf['modifiedAddress'][$key] .= '>>' . $this->_qnordersdf['oaid'] . $hashCode;
                    }
                }
            }
        }
        $modifiedAddress =  $this->_qnordersdf['modifiedAddress'];
        //比较地址是否有差异
        $area = $modifiedAddress['province'].'/'.$modifiedAddress['city'].'/'.$modifiedAddress['area'];
        kernel::single('ome_func')->region_validate($area);
        $this->_qnordersdf['consignee'] = array(//area,addr,zip,mobile
                'name'      =>  $modifiedAddress['name'] ? $modifiedAddress['name'] : $this->_order_detail['consignee']['name'],
                'area'      =>  $area,
                'addr'      =>  $modifiedAddress['town']? $modifiedAddress['town'].$modifiedAddress['addressDetail'] : $modifiedAddress['addressDetail'],
                'zip'       =>  $modifiedAddress['postCode'],
                'mobile'    =>  $modifiedAddress['phone'] ? $modifiedAddress['phone'] : $this->_order_detail['consignee']['mobile'],
        );

    }

}
