<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 盘点单
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_inventory extends erpapi_store_request_abstract
{

    /**
     * 商品同步pos
     *
     * @return void
     * @author
     **/
    public function inventory_check($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'盘点单审核';

        
      
        $params = $this->_format_check_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_check_apiname();
        if(!$method){
            return $this->succ('未定义无需同步');
        }


        $result = $this->call($method, $params, null, $title, 30, $sdf['inventory_bn']);
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
     * 盘点拒绝
     *
     * @return void
     * @author
     **/
    public function inventory_cancel($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'盘点单审核拒绝';

        
      
        $params = $this->_format_cancel_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_cancel_apiname();
        if(!$method){
            return $this->succ('未定义无需同步');
        }

        $result = $this->call($method, $params, null, $title, 30, $sdf['inventory_bn']);
        return $result;

    }
  
}
