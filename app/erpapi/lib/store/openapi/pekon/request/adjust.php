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
class erpapi_store_openapi_pekon_request_adjust extends erpapi_store_request_adjust
{

    

    protected function get_create_apiname()
    {
        return 'CreateAdjustDocument';
    }

    protected function _format_create_params($sdf)
    {
        $branch_bn = $sdf['branch_bn'];
        if(strpos($branch_bn, '_')){
            $branch_bn = explode('_',$branch_bn);
            $warehouseCode = $branch_bn[1];

        }else{
            $warehouseCode = POS_DEFAULT_BRANCH;
        }

        $params = array(
            'thirdPartyDocNo'       =>  $sdf['diff_bn'],
            'orgCode'               =>  $sdf['store_bn'],
            'warehouseCode'         =>  $sdf['branch_bn'],
            'referenceCheckOrderNo' =>  $sdf['inventory_bn'],
            'docDate'               =>  $sdf['at_time'],

        );
        $items = [];
        $line_i = 0;
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $line_i++;
                $items[] = [
                    'number'                => $line_i,//明细行序号

                    'skuCode'               => $v['material_bn'],//商品编码
                    'warehouseCode'         => $warehouseCode,

                    'quantity'              => $v['number'],//调整数量
                   
                ];
            }
        }
        $params['items'] = $items;

        return $params;
    }
   
  
    /**
     * 调整单审核
     *
     * @return void
     * @author
     **/

    public function adjust_check($sdf){
        $title = $this->__channelObj->wms['channel_name'].'调整单审核';

        $params = array(
            'docNo'         =>  $sdf['adjust_bn'],
            'auditAction'   =>  'APPROVE',
        );
       

        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

        $method = 'AuditAdjustDocument';
        if(!$method){
            return $this->error('方法为空');
        }


        $result = $this->call($method, $params, null, $title, 30, $sdf['adjust_bn']);
        return $result;


    }

    /**
     * 调整单取消
     *
     * @return void
     * @author
     **/
    public function adjust_cancel($sdf){
        $title = $this->__channelObj->wms['channel_name'].'调整单取消';

        $params = array(
            'docNo'         =>  $sdf['adjust_bn'],
            'auditAction'   =>  'REJECT',
            'auditReason'   =>  '取消',
        );
       

        $method = 'AuditAdjustDocument';
       

        $result = $this->call($method, $params, null, $title, 30, $sdf['adjust_bn']);
        return $result;

        
    }
}
