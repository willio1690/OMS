<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 有赞售后扩展
 * sunjing@shopex.cn
 */
class ome_aftersale_request_youzan extends ome_aftersale_abstract{
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }
   
    function show_aftersale_html(){

        $html = '';
        return $html;
    }

    /**
     * 退款申请通过/拒绝
     * @param  [type] $apply_id 退款申请id
     * @param  [type] $data     array
     * @return [type]           [description]
     */
    public function pre_save_refund($apply_id,$data)
    {
        $rs = array('rsp'=>'succ','msg'=>'成功','data'=>'');

        $refunddata = app::get('ome')->model('refund_apply')->refund_apply_detail($apply_id);

        if ($data['status'] == '3') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,$data['status'],'sync');
            return $result;
        }
    }

    public function pre_save_return($data)
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status    = $data['status'];
        $oReturn = app::get('ome')->model('return_product');
        $return = $oReturn->dump($return_id,'*');
        if ($status == '3' || $status == '5') {
            if ($return['return_type'] == 'change'){
                if($status == '3'){
                    $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'6','sync');
                }
                
                if($status == '5'){
                    $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'9','sync');
                }

            }else{
                $rsp = kernel::single('ome_service_aftersale')->update_status($return_id, $status, 'sync', $memo);

            }
            
            if ($rsp  && $rsp['rsp'] == 'fail') {
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp['msg'];
            }
        }

        return $rs;
    }

    function refund_detail($refundinfo)
    {
        $apply_id = $refundinfo['apply_id'];
        $shop_id  = $refundinfo['shop_id'];

        $ref = app::get('ome')->model ( 'refund_apply_youzan' )->dump(array('apply_id'=>$apply_id,'shop_id'=>$shop_id));
        if ($ref) {
            $refundinfo = array_merge($refundinfo,$ref);
        }

        $product_data = $refundinfo['product_data'];
        if ($product_data) {
            $product_data = unserialize($product_data);
        }
        $refundinfo['product_data'] = $refundinfo['product_data'] ? unserialize($refundinfo['product_data']) : array ();
        $refundinfo['online_memo'] = $refundinfo['online_memo'] ? unserialize($refundinfo['online_memo']) : array ();

        $this->_render->pagedata['refundinfo'] = $refundinfo;

        $html = $this->_render->fetch('admin/refund/plugin/refund_youzan.html');

        return $html;
    }

    /**
     * return_product_detail
     * @param mixed $returninfo returninfo
     * @return mixed 返回值
     */
    public function return_product_detail($returninfo)
    {
        $return_id = $returninfo['return_id'];
        $shop_id   = $returninfo['shop_id'];

        $ref = app::get('ome')->model ( 'return_product_youzan' )->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));

        if ($ref) $returninfo = array_merge($returninfo,$ref);
        
        $returninfo['online_memo'] = $returninfo['online_memo'] ? unserialize($returninfo['online_memo']) : array ();
        
        $this->_render->pagedata['return_product_youzan'] = $returninfo;
        
        $html = $this->_render->fetch('admin/return_product/plugin/detail_youzan.html');
        return $html;
    }
}
?>