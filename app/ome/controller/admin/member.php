<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_member extends desktop_controller{
    var $name = "会员";
    var $workground = "invoice_center";

    function index(){
        $this->finder('ome_mdl_members',array(
            'title' => '客户管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'actions' => array(
                    array(
                        'label' => '添加会员',
                        'href' => 'index.php?app=ome&ctl=admin_member&act=addMember',
                        'target' => "_blank",
                    ),
            ),
       ));
    }

    function addMember(){

        $shop_id = $_GET['shop_id'];
        if($shop_id){
            $shop_id = explode('*',$shop_id);
            $shop_id = $shop_id[0];
            $this->pagedata['shop_id'] = $shop_id;
        }
        $this->display("admin/member/add_member.html");
    }

    function editMember($member_id){
        $objMember = $this->app->model('members');
        $member = $objMember->dump(array('member_id'=>$member_id));
        $this->pagedata['member'] = $member;
        $this->pagedata['flag'] = md5($member['member_id']);
        $this->display("admin/member/add_member.html");
    }

    function doAddMember(){
        $post = $_POST;
        $post['uname_md5'] = md5($post['account']['uname']);
        $sameName = array('uname_md5'=>$post['uname_md5'], 'shop_id'=>$post['shop_id']);
        $mObj = $this->app->model("members");
        if($post['member_id']) {
            if(md5($post['member_id']) != $post['flag']) {
                echo json_encode(array('succ'=>'false', 'msg'=>'编辑失败，请重新点击编辑'));
                exit();
            }
            unset($post['flag']);
            if(!$mObj->dump(array('member_id'=>$post['member_id']))) {
                echo json_encode(array('succ'=>'false', 'msg'=>'编辑失败，没有这个用户'));
                exit();
            }
            $sameName['member_id|noequal'] = $post['member_id'];
        }
        $member = $mObj->dump($sameName,'member_id');
        if ($member){
            echo json_encode(array('succ'=>'false', 'msg'=>'操作会员失败，可能用户名重复'));
            exit;
        }

        $mem = $post;
        $shop_id = $post['shop_id'];
        $shopObj = app::get('ome')->model('shop');
        $shop_detail = $shopObj->dump(array('shop_id'=>$shop_id),'shop_type');
        if($shop_detail){
            $mem['shop_id'] = $shop_id;
            $mem['shop_type'] = $shop_detail['shop_type'];
        }
        $mem['sex']=$mem['profile']['gender'];
        if ($mObj->save($mem)){
            //新增地址
            if($mem['member_id']){
                $address_data = array(
                    'ship_name'     =>  $mem['account']['uname'],
                    'ship_area'     =>  $mem['contact']['area'],
                    'ship_mobile'   =>  $mem['contact']['phone']['mobile'],
                    'ship_tel'      =>  $mem['contact']['phone']['telephone'],
                    'ship_zip'      =>  $mem['contact']['zipcode'],
                    'ship_email'    =>  $mem['contact']['email'],
                    'member_id'     =>  $mem['member_id'],
                );

                $addressObj = app::get('ome')->model('member_address');

                $addressObj->create_address($address_data);
            }

            $data = $mObj->getList('member_id,uname,area,mobile,email,sex',array('member_id'=>$mem['member_id']),0,-1);
            if ($data)
            foreach ($data as $k => $v){
                $data[$k]['sex'] = ($v['sex']=='male') ? '男' : '女';
            }
            echo json_encode($data);
        }else{
            echo json_encode(array('succ'=>'false', 'msg'=>'操作失败'));
        }
    }

    /**
     * showSensitiveData
     * @param mixed $order_id ID
     * @param mixed $fieldType fieldType
     * @return mixed 返回值
     */
    public function showSensitiveData($order_id,$fieldType='')
   {
        // if (!kernel::single('desktop_user')->has_permission('sensitive_data_show')) {
        //     $this->splash('error',null,'您无权查看该数据');
        // }

        $order = app::get('ome')->model('orders')->db_dump(array('order_id'=>$order_id),'member_id,order_bn,shop_type,shop_id');

        if (!$order) {
            $order = app::get('archive')->model('orders')->db_dump($order_id,'member_id,order_bn,shop_type,shop_id');
        }

        $member = array ();
        if ($order['member_id']) {
            $tradeId = $order['order_bn'];

            $member = app::get('ome')->model('members')->db_dump($order['member_id'],'uname,mobile,tel,name,email,shop_type');

            $member['shop_id']  = $order['shop_id'];
            $member['order_bn'] = $order['order_bn'];
            $member['order_id'] = $order_id;

            $member['encrypt_body'] = kernel::single('ome_security_router',$order['shop_type'])->get_encrypt_body($member, 'member', $fieldType);
        }


        // 推送日志
        if($tradeId) kernel::single('base_hchsafe')->order_log(array('operation'=>'查看购买人信息','tradeIds'=>array($tradeId)));

        $this->splash('success',null,null,'redirect',$member);
   }

}

