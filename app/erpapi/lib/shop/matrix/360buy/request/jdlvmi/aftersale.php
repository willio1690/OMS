<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc
 * @author: sunjing
 * @since: 2023-7-18
 */
class erpapi_shop_matrix_360buy_request_jdlvmi_aftersale extends erpapi_shop_request_aftersale {
    

    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf)
    {
        
        $return_id = $sdf['return_id'];

        $reshipModel = app::get('ome')->model('reship');
        $reships = $reshipModel->db_dump(array('return_id'=>$return_id,'source'=>'matrix'),'shop_id,reship_bn,branch_id,t_end,reship_id');
        $reship_id = $reships['reship_id'];
        $itemsMdl = app::get('ome')->model('reship_items');
        $items = $itemsMdl->getlist('*',array('reship_id'=>$reship_id));
        $sdf['reship_bn'] = $reships['reship_bn'];
        $sdf['items'] = $items;

        $title = '退货入库单关单回传['.$reships['reship_bn'].']';

        $params = $this->get_returngoods_confirm_params($sdf);

        
        $rs= $this->__caller->call(SHOP_VMI_RETURN_GOOD_CONFIRM, $params, array(), $title, 10, $sdf['reship_bn']);


    }

    /**
     * 获取_returngoods_confirm_params
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function get_returngoods_confirm_params($sdf){

        $config = $this->__channelObj->channel['config'];
        $branch_id = $sdf['branch_id'];


        $platforms = kernel::single('ome_wms')->getPlatformBranchs($branch_id,'jdlvmi');
        $t_end = $sdf['t_end'] ? date('Y-m-d H:i:s',$sdf['t_end']): '';
        $params = array(

            'tenantId'          =>  $config['tenantid'],
            'warehouseNo'       =>  $platforms['relation_branch_bn'],
            'ownerCode'         =>  $config['ownercode'],
            'refund_id'         =>  $sdf['reship_bn'],
            'orderConfirmTime'  =>  $t_end,
            'remark'            =>  '',


        );

        $rtwOrderItems = $rtwOrderItem = array();
        $items = array();


        foreach($sdf['items'] as $k=>$v){
            if(empty($v['shop_goods_bn'])) continue;
            $items[$v['obj_type']][$v['shop_goods_bn']]= $v;
        }

 
        foreach($items as $obj_type=>$v){
            if($obj_type == 'pkg'){

            }
            foreach($v as $v){
                $rtwOrderItem[]= array(
                
                    'itemCode'      =>  $v['shop_goods_bn'],
                    'planQty'       =>  $v['quantity'],
                    'actualQty'     =>  $v['normal_num']+$v['defective_num'],

                );
            }
            
        }

       // $rtwOrderItems['rtwOrderItem'] = $rtwOrderItem;

        $params['item_list'] = json_encode($rtwOrderItem);
        //$params['rtwOrderItems'] = json_encode($rtwOrderItems);
        return $params;
    }
}