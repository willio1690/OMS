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
class erpapi_store_openapi_pekon_request_appropriation extends erpapi_store_request_appropriation
{

    

    protected function _format_check_params($sdf)
    {

        if($sdf['bill_type'] == 'o2otransfer'){
            $params = array(
                'docNo'         =>  $sdf['appropriation_no'],
                'auditAction'   =>  'APPROVE',
                'auditPerson'   =>  'outApprove',
            );
        }else if($sdf['bill_type'] == 'returnnormal'){
            $params = array(
                'docNo'         =>  $sdf['appropriation_no'],
                'auditAction'   =>  'APPROVE',
               
            );
        }else{
            $params= [];
        }
        

        return $params;

    }

    protected function get_check_apiname($sdf)
    {
        if($sdf['bill_type'] == 'o2otransfer'){
            return '';//2023年1月4号老板们确定不用
        }else if($sdf['bill_type'] == 'returnnormal'){
            return 'AuditStockReturnDocument';
        }else{
            return true;
        }

    }

    /**
     * _format_audit_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_audit_params($sdf){
        $params = array(
            'docNo'         =>  $sdf['appropriation_no'],
            'auditAction'   =>  'APPROVE',
            'auditPerson'   =>  'inApprove',
        );

        return $params;
    }

   
    /**
     * 获取_audit_apiname
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function get_audit_apiname($sdf){
        $bill_type = $sdf['bill_type'];
        if($bill_type == 'o2otransfer'){
            return '';//2023年1月4号老板们确定不用
        }else{
            return '';
        }
    }
}
