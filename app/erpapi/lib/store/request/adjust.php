<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 调整单
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_adjust extends erpapi_store_request_abstract
{

    /**
     * 调整单创建
     *
     * @return void
     * @author
     **/
    public function adjust_create($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'调整单创建';

        
      
        $params = $this->_format_create_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_create_apiname();
        if(!$method){
            return $this->succ('未定义无需同步');
        }


        $result = $this->call($method, $params, null, $title, 30, $sdf['diff_bn']);
        return $result;

    }

    protected function get_create_apiname()
    {
        return '';
    }

    protected function _format_create_params($sdf)
    {
    }
   
    /**
     * 调整单审核
     *
     * @return void
     * @author
     **/

    public function adjust_check($sdf){

    }

    /**
     * 调整单取消
     *
     * @return void
     * @author
     **/
    public function adjust_cancel($sdf){
        
    }
}
