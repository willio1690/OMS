<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 调拔单
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_appropriation extends erpapi_store_request_abstract
{

    /**
     * 调拔单审核
     * 
     * @return void
     * @author
     * */

    public function appropriation_check($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'调拔单审核';

        $params = $this->_format_check_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_check_apiname($sdf);
        if(!$method){
            return $this->succ('未定义无需同步');
        }


        $result = $this->call($method, $params, null, $title, 30, $sdf['appropriation_no']);
       
        return $result;

    }


    protected function _format_check_params($sdf)
    {

        $params = array(
           
        );
            
       
        return $params;
    }

    protected function get_check_apiname($sdf)
    {


    }

        /**
     * appropriation_audit
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function appropriation_audit($sdf){
        $title = $this->__channelObj->wms['channel_name'].'调拔单审批';

        $params = $this->_format_audit_params($sdf);
       

        if (!$params) {
            return $this->succ('未定义无需同步');
        }

        $method = $this->get_audit_apiname($sdf);
        if(!$method){
            return $this->succ('未定义无需同步');
        }

        $result = $this->call($method, $params, null, $title, 30, $sdf['appropriation_no']);
        return $result;
    }

    protected function _format_audit_params($sdf)
    {

        $params = array(
           
        );
            
       
        return $params;
    }

    protected function get_audit_apiname($sdf)
    {

        
    }

}
