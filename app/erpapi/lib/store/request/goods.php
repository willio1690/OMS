<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_goods extends erpapi_store_request_abstract
{

    /**
     * 商品同步pos
     * 
     * @return void
     * @author
     * */

    public function goods_add($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        

        $params = $this->_format_goods_params($sdf);
       

        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        $method = $this->get_goods_add_apiname();
        if(!$method){
            return $this->error('方法为空');
        }

        $result = $this->call($method, $params, null, $title, 30, $sdf['material_bn']);
        return $result;

    }


    protected function _format_goods_params($p)
    {

        $params = array(
           
        );
            
       
        return $params;
    }

    protected function get_goods_add_apiname()
    {


    }

        /**
     * goods_syncprice
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function goods_syncprice($sdf){
        $title = $this->__channelObj->wms['channel_name'].'价格同步';
        $params = $this->_format_syncprice_params($sdf);
     
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        $method = $this->get_goods_syncprice_apiname();


        if(!$method){
            return $this->error('方法为空');
        }
        
        $result = $this->call($method, $params, null, $title, 30, $sdf['material_bn']);


        return $result;
    }

    /**
     * _format_syncprice_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function _format_syncprice_params($sdf){
        $params = array();
        return $params;
    }

    protected function get_goods_syncprice_apiname()
    {

    }
}
