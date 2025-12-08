<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_tmall extends ome_aftersale_abstract{


    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_render = app::get('ome')->render();
    }

    /**
     * 退款申请详情扩展.
     * 
     * 
     * 
     * @author
     */
    function refund_detail($refundinfo)
    {
        $apply_id = $refundinfo['apply_id'];
        $shop_id = $refundinfo['shop_id'];

        $oRefund_tmall = app::get('ome')->model ( 'refund_apply_tmall' );
        $refund_tmall = $oRefund_tmall->dump(array('apply_id'=>$apply_id,'shop_id'=>$shop_id));
        if ($refund_tmall) {
            $refundinfo = array_merge($refundinfo,$refund_tmall);
        }
        $product_data = $refundinfo['product_data'];
        if ($product_data) {
            $product_data = unserialize($product_data);
        }
        $tag_list = $refundinfo['tag_list'];
        if ($tag_list) {
            $tag_list = unserialize($tag_list);

            $refundinfo['tag_list'] = is_array($tag_list) ? $tag_list : [];
        } else {
            $refundinfo['tag_list'] = [];
        }

        $refundinfo['product_data'] = $product_data;
        $online_memo = $refundinfo['online_memo'];
        if ($online_memo) {
            $online_memo = unserialize($online_memo);
            $refundinfo['online_memo'] = $online_memo;
        }
	#商家已发货、仅退款、买家未收到货且退款单状态为REFUND_WAIT_SELLER_AGREE（买家已经申请退款，等待卖家同意）
        $this->_render->pagedata['opt'] = [];
        if(in_array($refundinfo['source_status'], ['REFUND_WAIT_SELLER_AGREE','WAIT_SELLER_AGREE'])
            && strtolower($refund_tmall['has_good_return']) == 'false'
            && $refund_tmall['order_status'] == 'WAIT_BUYER_CONFIRM_GOODS'
            //&& $refundinfo['status'] != '0'
        ) {
            $rsp = kernel::single('erpapi_router_request')->set('shop',$refundinfo['shop_id'])->finance_refundDetailGet($refundinfo);
            if($rsp['rsp'] == 'succ' && is_array($rsp['data'])) {
                $this->_render->pagedata['opt'] = $rsp['data'];
            }
        }
        $this->_render->pagedata['refundinfo'] = $refundinfo;

        unset($refundinfo);
        $html = $this->_render->fetch('admin/refund/plugin/refund_tmall.html');

        return $html;
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
        $return_id = $returninfo['return_id'];
        $shop_id = $returninfo['shop_id'];
        $oReturn_product_tmall = app::get('ome')->model ( 'return_product_tmall' );
        $oReturn_address = app::get('ome')->model ( 'return_address' );
        $return_product_tmall = $oReturn_product_tmall->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));
        if ($return_product_tmall['contact_id']) {
            $address = $oReturn_address->dump(array('contact_id'=>$return_product_tmall['contact_id']));
            if ($address) {
                $return_product_tmall['address'] = $address['province'].$address['city'].$address['country'].$address['addr'];
            }
        }else{
            $default_address = $oReturn_address->getDefaultAddress($shop_id);
            $return_product_tmall['address'] = $default_address['address'];
            $return_product_tmall['contact_id'] = $default_address['contact_id'];
        }
        $return_product_tmall = array_merge($return_product_tmall);
        $html = 'admin/return_product/plugin/edit_tmall.html';
        $this->_render->pagedata['return_product_tmall'] = $return_product_tmall;
        unset($return_product_tmall);
        $html = $this->_render->fetch($html);
        return $html;
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
        $oReturn_product_tmall = app::get('ome')->model ( 'return_product_tmall' );
        $data = array(
            'contact_id' => $data['contact_id'],

            'shop_id'=>$data['shop_id'],
            'return_id'=>$data['return_id'],
        );

        $oReturn_product_tmall->save($data);
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
        $return_id = $returninfo['return_id'];
        $shop_id = $returninfo['shop_id'];
        $oReturn_product_tmall = app::get('ome')->model ( 'return_product_tmall' );
        $return_product_tmall = $oReturn_product_tmall->dump(array('return_id'=>$return_id,'shop_id'=>$shop_id));

        $return_product_tmall['tag_list'] = unserialize($return_product_tmall['tag_list']);

        $oAddress = app::get('ome')->model ( 'return_address' );
        if ($return_product_tmall['contact_id']) {
            $address = $oAddress->dump(array('contact_id'=>$return_product_tmall['contact_id']));
            if ($address) {
                $return_product_tmall = array_merge($return_product_tmall,$address);
            }
        }
        if ($return_product_tmall['online_memo']) {
            $online_memo = unserialize($return_product_tmall['online_memo']);

            foreach($online_memo as &$memo){
                $memo['content'] = str_replace('\n',"<br>",$memo['content']);

            }
            $return_product_tmall['online_memo'] = $online_memo;
        }

        $this->_render->pagedata['return_product_tmall'] = $return_product_tmall;
        $interceptItem = [];
        if(strstr($return_product_tmall['attribute'],'interceptItemListResult')) {
            preg_match_all('/interceptItemListResult:([^;]+);/', $return_product_tmall['attribute'], $matches);
            if($matches && $matches[1] && $matches[1][0]) {
                $intercept = json_decode(str_replace("#3B", ":", $matches[1][0]), 1);
                if($intercept[0]['autoInterceptAgree'] == 1) {
                    $interceptItem = $intercept[0]; 
                    $logisticInterceptEnum = [
                        'INTERCEPT_APPLY' => '物流截单申请中',
                        'INTERCEPT_SUCCESS' => '物流截单成功',
                        'INTERCEPT_FAILED' => '物流截单失败',
                        'INTERCEPT_SELLER_CONFIRM_SIGN' => '拦截卖家确认签收',
                    ];
                    $interceptItem['logisticInterceptEnum'] = $logisticInterceptEnum[$interceptItem['logisticInterceptEnum']] ? : $interceptItem['logisticInterceptEnum'];
                }
            }
        }
        $this->_render->pagedata['intercept'] = $interceptItem;

        $html = $this->_render->fetch('admin/return_product/plugin/detail_tmall.html');
        return $html;
    }



    /**
     * 退款状态保存前扩展
     * @param   data msg
     * @return
     * @access  public
     * @author
     */
    function pre_save_refund($apply_id,$data)
    {
        set_time_limit(0);

        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '2') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,2,'sync');


            return $result;
		}


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
        $status = $data['status'];
        $oReturn = app::get('ome')->model('return_product');
        $return = $oReturn->dump($return_id,'*');
        $oReturn_tmall = app::get('ome')->model('return_product_tmall');
        $oReturn_address = app::get('ome')->model('return_address');
        $return_tmall = $oReturn_tmall->dump(array('return_id'=>$return_id));
        $contact_id = $return_tmall['contact_id'];
        $return_address = $oReturn_address->dump(array('shop_id'=>$return['shop_id'],'cancel_def'=>'true'));
        if ($status == '3') {#
            if ($contact_id<=0 && !$return_address) {
                $rs['rsp'] = 'fail';
                $rs['msg'] = '卖家退货信息不可为空或当前店铺没有默认地址!';
            }else{
                //同意退货是3 同意换货是6重新定义值好判断返回对应请求方法
                if ($return_tmall['refund_type'] == 'change'){
                    $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'6','sync');


                }else{
                    $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'3','sync');
                }

                if ($rsp  && $rsp['rsp'] == 'fail') {
                    $rs['rsp'] = 'fail';
                    $rs['msg'] = $rsp['msg'];
                }
            }


        }

        return $rs;
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


    function return_api(){
        return true;
    }

    /**
     * 保存退款时按钮直接跳转还是dialog
     * @param
     * @return
     * @access  public
     * @author
     */
    function refund_button($apply_id,$status)
    {
        $rs = array('rsp'=>'default','msg'=>'成功','data'=>'');
        if ($status == '3') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_refund_apply&act=upload_refuse_message&p[0]='.$apply_id.'&p[1]=tmall');
        }
        return $rs;
    }


    /**
     * 售后拒绝时弹出的页面.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '5') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=tmall');
        }
        return $rs;
    }

    /**
     * 退货详情.
     * @param   array returninfo
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function reship_edit($returninfo)
    {
        $oReturn_product_tmall = app::get('ome')->model ( 'return_product_tmall' );
        $returninfo = $oReturn_product_tmall->dump(array('return_id'=>$returninfo['return_id']));

        if ($returninfo['online_memo']) {
            $returninfo['online_memo'] = unserialize($returninfo['online_memo']);
        }

        $this->_render->pagedata['returninfo'] = $returninfo;
        $html = $this->_render->fetch('admin/return_product/plugin/reship_taobao.html');
        return $html;
    }
}
?>
