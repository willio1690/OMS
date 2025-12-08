<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_shop extends erpapi_store_request_abstract
{

    /**
     * 商品同步pos
     *
     * @return void
     * @author
     **/
    public function shop_add($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'门店同步';

        

        $params = $this->_format_shop_add_params($sdf);
       

        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        $method = $this->get_shop_add_apiname();
        if(!$method){
            return $this->error('方法为空');
        }

        $result = $this->call($method, $params, null, $title, 30, $sdf['store_bn']);


        return $result;

    }


    protected function _format_shop_add_params($sdf)
    {

        $params = array(
           
        );
            
       
        return $params;
    }

    protected function get_shop_add_apiname()
    {


    }

    
}
