<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * CONFIG
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_config extends erpapi_wms_openapi_config
{
    public $datetime;
    private $_method_mapping = array(
        WMS_ITEM_ADD => 'pushProduct',
        WMS_ITEM_UPDATE => 'pushProduct',
        WMS_INORDER_CREATE => 'pushPurchase',
        WMS_INORDER_CANCEL => 'cancelPurchase',
        WMS_OUTORDER_CREATE => 'pushB2B',
        WMS_OUTORDER_CANCEL => 'cancelOrder',
        WMS_SALEORDER_CREATE => 'pushOrder',
        WMS_SALEORDER_CANCEL => 'cancelOrder',
        WMS_RETURNORDER_CREATE => 'pushBackOrder',
        WMS_RETURNORDER_CANCEL => 'cancelPurchase',
        WMS_TRANSFERORDER_CREATE => '',
    );

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct(){
        $this->datetime = $_POST['timestamp'] ? date('Y-m-d H:i:s', $_POST['timestamp']) : date('Y-m-d H:i:s');
    }
    /**
     * 定义应用参数
     * 
     * @return void
     * @author 
     * */
    public function define_query_params(){
        $params  = array( 
            'label'=>'sku360',
            'desc'=>'desc',
             'params' => array(
                 'appkey' =>'appkey',
                 'owner' => 'owner'
            ),
        );
        return $params;
    }

    public function get_query_params($method, $params){
        $query_params = array(
            'method'       => $this->_method_mapping[$method],
            'datetime'     => $this->datetime,
        );

        return $query_params;
    }

    public function gen_sign($params){
        $owner = $this->__channelObj->wms['adapter']['config']['owner'];
        $appkey = $this->__channelObj->wms['adapter']['config']['appkey'];
        $datetime = $this->datetime;
        return strtoupper(md5($owner . $appkey . $datetime));
    }

        /**
     * 获取_url
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $realtime realtime
     * @return mixed 返回结果
     */
    public function get_url($method, $params, $realtime){
        $url = $this->__channelObj->wms['adapter']['config']['api_url'];
        $owner = $this->__channelObj->wms['adapter']['config']['owner'];
        return $url . '?owner=' . $owner;
    }
}