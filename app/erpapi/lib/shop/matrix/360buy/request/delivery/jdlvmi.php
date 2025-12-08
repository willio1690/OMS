<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_shop_matrix_360buy_request_delivery_jdlvmi extends erpapi_shop_request_delivery
{

   

    /**
     * 发货请求参数
     * 
     * @return void
     * @author
     * */

    protected function get_confirm_params($sdf)
    {
        $config = $this->__channelObj->channel['config'];

        $branch_id = $sdf['branch_id'];
        $platforms = kernel::single('ome_wms')->getPlatformBranchs($branch_id,'jdlvmi');

        $sdf['delivery_time'] = time()+1;
        $params = array(

            'tenantId'          =>  $config['tenantid'],
            'tid'               =>  $sdf['orderinfo']['order_bn'],
            'warehouseNo'       =>  $platforms['relation_branch_bn'],
            'ownerCode'         =>  $config['ownercode'],
            'orderType'         =>  'XSCK',
            'orderConfirmTime'  =>  $sdf['delivery_time'] ? date('Y-m-d H:i:s',$sdf['delivery_time']) : date('Y-m-d H:i:s'),

        );
        $package= $orderLine= array();


        $product_list = array(); 
        $i=0;

        $shop_goods = array();
        foreach($sdf['orderinfo']['order_objects'] as $order_object){
           
            $shop_goods[$order_object['bn']] =  $order_object['shop_goods_id'];
           

            $orderLine[] = array(

               
                'itemCode'      =>  $order_object['shop_goods_id'],
        
                'itemStatus'    =>  '100',
                'planQty'       =>  $order_object['quantity'],
                'actualQty'     =>  $order_object['quantity'],

            );

        }
        $package = array();
        foreach($sdf['packages'] as $k=>$pv){

            $packageItems = array();

            foreach($pv as $v){
                $bn = $v['bn'];
                $packageItems[] = array(

                    'itemCode'  =>  $shop_goods[$bn],
                    'quantity'  =>  $v['number'],
                    'itemStatus'=>  '100',

                );
            }
           
            $package[] = array(
               
                'logisticsCode' =>  $sdf['logi_type'],
                'logistics_no'  =>  $sdf['logi_no'],
                'packageCode'   =>  $k,
                'packageItems'  =>  $packageItems,
            );
            
        }

       


        $params['packages'] = json_encode($package);
        $params['orderLines'] = json_encode( $orderLine);
      
        return $params;
    }

   
   protected function get_delivery_apiname($sdf)
    {
        $api_name = SHOP_WMI_LOGISTICS_OFFLINE_SEND;
        return $api_name;
    }


        /**
     * notify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function notify($sdf){

        $params = $this->get_notify_params($sdf);

        $title = sprintf('库内作业情况回传[%s]-%s',$sdf['order_bn'],$sdf['status']);

        $result = $this->__caller->call(SHOP_WMS_OUTORDER_NOTIFY, $params, $callback, $title, 10, $sdf['order_bn']);


        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'] ? json_decode($result['data'], true) : array();
        return $rs;
    }

    protected function get_notify_params($sdf)
    {
        
       
        $config = $this->__channelObj->channel['config'];

        $branch_id = $sdf['branch_id'];
        $platforms = kernel::single('ome_wms')->getPlatformBranchs($branch_id,'jdlvmi');
        $params = array(

            'tenantId'          =>  $config['tenantid'],
            'out_order_code'    =>  $sdf['order_bn'],
            'warehouse_code'    =>  $platforms['relation_branch_bn'],
            'ownerCode'         =>  $config['ownercode'],
            'operateTime'       =>  $sdf['delivery_time'] ? date('Y-m-d H:i:s',$sdf['delivery_time']): date('Y-m-d H:i:s',$sdf['operatetime']),
            'remark'            =>  '',
            'soStatus'          =>  $sdf['status'],

        );


        return $params;
    }
}
