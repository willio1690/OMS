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
class erpapi_store_openapi_pekon_request_shop extends erpapi_store_request_shop
{

    
    protected function _format_shop_add_params($sdf)
    {

        list($province, $city, $district) = explode('/', $sdf['area']);
        $params = array(
           'originDataId'           =>  $sdf['store_bn'],
           'orgBusinessTypeCode'    =>  'ADMIN',
           'code'                   =>  $sdf['store_bn'],
           'name'                   =>  $sdf['name'],
         
           'orgTypeId'              =>  'SHOP',
           'status'                 =>  'Y',//状态。标识数据是否有效。Y：有效   N：无效
           'originDataUpdatedTime'  =>  date('Y-m-d',$sdf['create_time']),
           'address'                =>  $sdf['addr'],//详细地址
           'telephone'              =>  $sdf['mobile'],
           'isValid'                =>  'Y',//是否正式数据
           
           'countryCode'            =>  'CN',//固定值：CN
           //'regionLeafCode'         =>  '',//区域编码
   
           'telephone'              =>  $sdf['mobile'],//联系电话
           //'openDate'               =>  ,
           //'closeDate'              =>  ,
           //'businessFromTime'       =>  ,
           //'businessToTime'         =>  ,
           'counterType'            =>  $sdf['store_sort'],
           'counterTypeName'        =>  $sdf['store_sort'],
           'provinceName'           =>  $province,
           'cityName'               =>  $city,
           'countyName'             =>  $district,

        );
            
       
        return $params;
    }

    protected function get_shop_add_apiname()
    {

        return 'synchOrganizationByInterface';
    }

    
}
