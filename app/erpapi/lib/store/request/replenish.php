<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 补货同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_replenish extends erpapi_store_request_abstract
{

    /**
     * 商品同步pos
     * 
     * @return void
     * @author
     * */

    public function replenish_check($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'商品添加';

        
      
        $params = $this->_format_check_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_check_apiname();
        if(!$method){
            return $this->succ('未定义无需同步');
        }

        $result = $this->call($method, $params, null, $title, 30, $sdf['material_bn']);
        return $result;

    }


    protected function _format_check_params($sdf)
    {

        $params = array(
           
        );
            
       
        return $params;
    }

    protected function get_check_apiname()
    {


    }

        /**
     * replenish_stockincreate
     * @return mixed 返回值
     */
    public function replenish_stockincreate(){

    }

   
}
