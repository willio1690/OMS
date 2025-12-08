<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_360buy extends ome_aftersale_abstract{


    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }


    /**
     * 是否继续转化类型扩展
     * @
     * @return  bool
     * @access  public
     * @author
     */
    function choose_type()
    {
        return false;
    }
    /**
     * 售后申请编辑前扩展
     * @param   array    $returninfo
     * @return
     * @access  public
     * @author
     */
    function pre_return_product_edit($returninfo)
    {
        $returnExtMdl = app::get('ome')->model('return_product_360buy');

        $returnExtData = $returnExtMdl->db_dump(array ('return_id' => $returninfo['return_id']));

        $filter = $returnExtData['contact_id'] ? array ('shop_id' => $returnExtData['shop_id'],'contact_id' => $returnExtData['contact_id'] ) : array ('shop_id' => $returnExtData['shop_id'], 'get_def' => 'true');

        $return_address = app::get('ome')->model('return_address')->db_dump($filter);

        $data['contact_id'] = $return_address['contact_id'];
        $data['address']    = $return_address['addr'];
        $data['shop_id']    = $return_address['shop_id'];

        $this->_render->pagedata['data'] = $data;

        $res = kernel::single('erpapi_router_request')->set('shop', $returninfo['shop_id'])->aftersale_getApproReason(array ('return_bn' => $returninfo['return_bn']));
        
        if ($res['rsp'] == 'succ' && $res['data']) {
            $this->_render->pagedata['approve_reasons'] = $res['data']; 
        }

        return $this->_render->fetch('admin/return_product/plugin/edit_360buy.html');
    }


    /**
     * 售后申请编辑后扩展
     * @param   array    data
     * @return
     * @access  public
     * @author
     */
    function return_product_edit_after($data)
    {
        #更新附加表操作
        $returnExtMdl = app::get('ome')->model ( 'return_product_360buy' );
        $data = array(
            'contact_id'     => $data['contact_id'],
            'shop_id'        => $data['shop_id'],
            'return_id'      => $data['return_id'],
            'approve_reason' => $data['approve_reason']?$data['approve_reason']:'1',
        );

        $returnExtMdl->save($data);
    }

    /**
     * 售后服务详情查看页扩展
     * @param   array    $returninfo
     * @return  html
     * @access  public
     * @author
     */
    public function return_product_detail($returninfo)
    {
        $returnExtMdl = app::get('ome')->model('return_product_360buy');

        $returnExtData = $returnExtMdl->db_dump(array ('return_id' => $returninfo['return_id']));
        $returnExtData['return_address'] = @json_decode($returnExtData['return_address'],true);
        $returnExtData['pick_address']   = @json_decode($returnExtData['pick_address'],true);
        $returnExtData['customer_info']  = @json_decode($returnExtData['customer_info'],true);
        $returnExtData['apply_detail']   = @json_decode($returnExtData['apply_detail'],true);
        $returnExtData['online_memo']   = @unserialize($returnExtData['online_memo']);

        $this->_render->pagedata['returnExtData'] = $returnExtData;
        return $this->_render->fetch('admin/return_product/plugin/detail_360buy.html');
    }
   
    /**
     * 售后保存前的扩展
     * @param
     * @return
     * @access  public
     * @author
     */
    function pre_save_return($data)
    {
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status    = $data['status'];


        if ($status == '3') {
            // $extra = app::get('ome')->model('return_product_360buy')->dump(array('return_id'=>$return_id));

            // $filter = $extra['contact_id'] ? array ('shop_id' => $extra['shop_id'],'contact_id' => $extra['contact_id'] ) : array ('shop_id' => $extra['shop_id'], 'get_def' => 'true');

            // $return_address = app::get('ome')->model('return_address')->db_dump($filter);

            // $memo = array (
            //     'extra'          => $extra,
            //     'return_address' => $return_address,
            // );

            //同意退货是3
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'3','sync',$memo);

            if ($rsp  && $rsp['rsp'] == 'fail') {
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp['msg'];
            }
        }

        return $rs;
    }
}
?>
