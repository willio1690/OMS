<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_security_kuaishou extends ome_security_hash{
    // 订单加密字段
    protected $_order_encrypt = array(
        'ship_name'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/name',
            'pddkey' => 'receiver_name',
        ),
        'ship_tel'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/telephone',
            'pddkey' => 'receiver_phone',
        ),
        'ship_mobile' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
            'pddkey' => 'receiver_phone',
        ),
        'ship_addr'   => array(
            'type' => 'simple',
            'sdf'  => 'consignee/addr',
            'pddkey' => 'receiver_address',
        ),
        'uname' => array (
            'type' => 'simple',
            'jdkey' => 'uname',
            'pddkey' => 'receiver_name',

        ),
    );

    // 会员加密字段
    protected $_member_encrypt = array(
        'uname'  => array(
            'type' => 'simple',
            'sdf'  => 'account/uname',
        ),
        'name'   => array(
            'type' => 'simple',
            'sdf'  => 'contact/name',
            'jdkey' => 'uname',
        ),
        'mobile' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
            'pddkey' => 'receiver_phone',
        ),
        'tel' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
            'pddkey' => 'receiver_phone',
        ),
        'email' => array(
            'type' => 'simple',
            'sdf'  => 'contact/email',
        ),
    );

    // 发货单加密字段
    protected $_delivery_encrypt = array(
        'ship_name'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/name',
            'pddkey' => 'receiver_name',
        ),
        'ship_tel'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/telephone',
            'pddkey' => 'receiver_phone',
        ),
        'ship_mobile' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
            'pddkey' => 'receiver_phone',
        ),
        'ship_addr'   => array(
            'type' => 'simple',
            'sdf'  => 'consignee/addr',
            'pddkey' => 'receiver_address',
        ),
        'uname' => array (
            'type' => 'simple',
            'jdkey' => 'uname',
            'pddkey' => 'receiver_name',
        ),
    );

    protected $_sales_delivery_encrypt = array (
        'ship_name'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/name',
        ),
        'ship_tel'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/telephone',
        ),
        'ship_mobile' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
        ),
        'ship_addr'   => array(
            'type' => 'simple',
            'sdf'  => 'consignee/addr',
        ),
        'uname' => array (
            'type' => 'simple',
            'jdkey' => 'uname',
        ),
    );

    // 开票加密字段
    protected $_invoice_encrypt = array (
        'ship_tel' => array(
            'type' => 'simple',
        ),
        'ship_addr' => array(
            'type' => 'simple',
        ),
        'ship_bank_no' => array(
            'type' => 'simple',
            'jdkey' => 'invoice_bank_account',
        ),
        'tax_company' => array(
            'type' => 'simple',
            'jdkey' => 'ship_name',
        ),
    );

    // 跨境加密字段
    protected $_customs_encrypt = array (
        'member_uname' => array(
            'type' => 'simple',
            'jdkey' => 'uname',
        ),
        'member_name' => array(
            'type' => 'simple',
            'jdkey' => 'uname',
        ),
        'member_mobile' => array(
            'type' => 'simple',
            'jdkey' => 'ship_mobile',
        ),
    );

    // 订单加密字段
    protected $_reship_encrypt = array(
        'ship_name'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/name',
        ),
        'ship_tel'    => array(
            'type' => 'simple',
            'sdf'  => 'consignee/telephone',
        ),
        'ship_mobile' => array(
            'type' => 'simple',
            'sdf'  => 'consignee/mobile',
        ),
        'ship_addr'   => array(
            'type' => 'simple',
            'sdf'  => 'consignee/addr',
        ),
        'uname' => array (
            'type' => 'simple',
            'jdkey' => 'uname',
        ),
    );

    // 订单加密字段
    protected $_aftersale_encrypt = array(
        'ship_mobile' => array(
            'type' => 'simple',
        ),
        'member_uname' => array (
            'type' => 'simple',
            'jdkey' => 'uname',
        ),
    );

    //原始密文
    protected $_original_fields = array(
        'uname' => 'receiver_name_index_origin',
        'ship_name' => 'receiver_name_index_origin',
        'ship_tel' => 'receiver_phone_index_origin',
        'ship_mobile' => 'receiver_mobile_index_origin',
        'ship_addr' => 'receiver_address_index_origin',
    );

    /**
     * 获取加密请求BODY
     * @param  [type] $order_id    [description]
     * @param  [type] $shop_id [description]
     * @param  string $type    [description]
     * @return [type]          [description]
     */
    public function get_encrypt_body($data, $type = 'order', $fieldType = '')
    {
        // 隐私加密不允许解密，直接返回空数组
        return array();
    }

    /**
     * 获取Filed
     * @param mixed $c c
     * @param mixed $fieldType fieldType
     * @return mixed 返回结果
     */
    public function getFiled($c,$fieldType){
        switch ($c) {
            case 'uname':
                $field = 'receiver_name';
                break;
            case 'name':
                $field = 'receiver_name';
                break;
            case 'tel':
                $field = 'receiver_phone';
                break;
            case 'region':
                $field = 'receiver_address';
                break;
            case 'address':
                $field = 'address';
                break;
            default:
                $field =  $c;
                break;
        }
        return $field;
    }

}