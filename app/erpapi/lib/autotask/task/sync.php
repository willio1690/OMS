<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 定时查询京东出入库接口 只取6小时前的记录
 *
 * 此类已弃用 Wed May  4 14:36:17 2022 chenping@shopex.cn
 *
 *
 * @category
 * @package
 * @author sunjing@shopex.cn
 * @version $Id: Z
 */
class erpapi_autotask_task_sync
{

    static $obj_type = array(

        'search_reship'             => array(
            'text' => '查询退货单状态',

        ),
        'search_delivery'           => array(
            'text' => '查询发货单状态',

        ),
        'search_inpurchase'         => array(

        ),
        'search_outpurchase_return' => array(
            'text' => '查询采购退货单',

        ),
        'search_outallcoate'        => array(
            'text' => '查询调拨出库单',

        ),
        'search_outdefective'       => array(
            'text' => '查询残损出库',

        ),
        'search_outother'           => array(
            'text' => '查询其他出库单',

        ),

        'search_outdirect'          => array(
            'text' => '查询直接出库单',

        ),
        'search_inallcoate'         => array(
            'text' => '查询调拨入库单',

        ),
        'search_indefective'        => array(
            'text' => '查询残损入库',

        ),
        'search_inother'            => array(
            'text' => '查询其他入库单',

        ),

        'search_indirect'           => array(
            'text' => '查询直接入库单',

        ),
    );

    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($params, $error_msg)
    {

        $apiModel = app::get('erpapi')->model('api_fail');
        $time     = time() - 3600;
        kernel::single('erpapi_misc_task')->minute();
        $search_time = strtotime('-1 month');

        $channelObj = app::get('channel')->model('channel');
        $channel_list = $channelObj->getlist('channel_id',array('node_type'=>'jd_wms_cloud'));
        $channel_id = $channel_list[0]['channel_id'];
        $channel_ids = array_map('current', $channel_list);

        $branch_list = $channelObj->db->select("SELECT branch_id FROM sdb_ome_branch WHERE wms_id in (".implode(',',$channel_ids).")");

        $branch_ids = array_map('current', $branch_list);

        $api_list = $apiModel->getlist('status,obj_type,params,fail_times,obj_bn,id', array('status' => 'fail', 'obj_type' => array_keys(self::$obj_type), 'create_time|than' => $search_time), 0, 500);

        $api_list2 = $apiModel->getlist('status,obj_type,params,fail_times,obj_bn,id', array('status' => 'running', 'obj_type' => array_keys(self::$obj_type), 'create_time|than' => $search_time, 'last_modify|lthan'=>time()-3600), 0, 500);
        $api_list = array_merge($api_list, $api_list2);
        foreach ($api_list as $api) {
            $apiModel->update(array('status' => 'running'), array('id' => $api['id']));
            if ($api['obj_type'] == 'search_delivery') {
                $delivery = app::get('ome')->model('delivery')->dump(array('delivery_bn' => $api['obj_bn'], 'status' => array('ready', 'progress'), 'parent_id' => 0), 'delivery_bn,branch_id,delivery_id');
                if ($delivery) {
                    $wms_id        = kernel::single('ome_branch')->getWmsIdById($delivery['branch_id']);
                    $notice_params = array(
                        'delivery_bn' => $delivery['delivery_bn'],
                        'delivery_id' => $delivery['delivery_id'],
                        'branch_id'   => $delivery['branch_id'],
                        'wms_id'      => $wms_id,
                        'obj_id'      => $api['id'],
                    );

                    $rs = ome_delivery_notice::search($notice_params, true);
                    if ($rs['rsp'] == 'fail' && $rs['res'] == 310) {
                        $apiModel->delete(array('id' => $api['id']));
                    }
                }
            }

        }

        //沧海其它出入库查询
        
        //采购入库
        $po_list = app::get('purchase')->model('po')->getlist('branch_id,out_iso_bn,po_bn',array('po_status'=>array('1'),'check_status'=>array('2'),'branch_id'=>$branch_ids));
        if($po_list){
            foreach($po_list as $po){
                $branch_id = $po['branch_id'];
                $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
                $data = array(
                    'out_order_code'    =>  $po['out_iso_bn'],
                    'stockin_bn'        =>  $po['po_bn'],
                
                    'obj_id'            =>  $api['id'],
                );
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockin_search($data);
            }
        }

        $return_purchase_list = app::get('purchase')->model('returned_purchase')->getlist('branch_id,out_iso_bn,rp_bn',array('check_status'=>array('2'),'branch_id'=>$branch_ids,'return_status'=>array('1')));
        if ($return_purchase_list){
            foreach($return_purchase_list as $po){
                $branch_id = $po['branch_id'];
                $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id );
                $data = array(
                    'out_order_code'    =>  $po['out_iso_bn'],
                    'stockout_bn'       =>  $po['rp_bn'],
                 
                  
                );
  
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_search($data);
            }
        }

        //入库
        $iniostock_type = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(1,true);
        $iniostock_type[] = 4;
        $iso_list = app::get('taoguaniostockorder')->model("iso")->getlist('iso_bn,branch_id,out_iso_bn',array('type_id'=>$iniostock_type,'iso_status'=>array('1'),'check_status'=>array('2'),'branch_id'=>$branch_ids));
        if ($iso_list){
            foreach($iso_list as $iso_data){
                $data = array(
                     'out_order_code'   =>  $iso_data['out_iso_bn'],
                     'stockin_bn'       =>  $iso_data['iso_bn'],
                    
                );
                $wms_id = kernel::single('ome_branch')->getWmsIdById($iso_data['branch_id']);
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockin_search($data);
            }
        }

        $outiostock_type = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(0,true);
        $outiostock_type[] = 40;
        $outiostock_list = app::get('taoguaniostockorder')->model("iso")->getlist('iso_bn,branch_id,out_iso_bn',array('type_id'=>$outiostock_type,'iso_status'=>array('1'),'check_status'=>array('2'),'branch_id'=>$branch_ids));
        if ($outiostock_list){
            foreach($outiostock_list as $outiostock){
                $data = array(
                     'out_order_code'   =>  $outiostock['out_iso_bn'],
                     'stockin_bn'       =>  $outiostock['iso_bn'],
                    
                );
                $wms_id = kernel::single('ome_branch')->getWmsIdById($outiostock['branch_id']);
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_search($data);
            }
        }

    }

}
