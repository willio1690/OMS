<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_customer extends desktop_controller{
    var $name = "会员";
    var $workground = "goods_manager";

    function index(){
        $this->finder('ome_mdl_members',array(
            'title' => '客户管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'actions' => array(
                    array(
                        'label' => '添加客户',
                        'href' => 'index.php?app=ome&ctl=admin_customer&act=add',
                        'target'=>"dialog::{width:800,height:700,title:'添加客户'}",
                    ),
                    array(
                        'label'=>'导出模板',
                        'href'=>'index.php?app=ome&ctl=admin_customer&act=exportTemplate',
                        'target'=>'_blank'
                    ),
            ),
       ));
    }

    function add($member_id=''){
        $finder_id = $_GET['finder_id'];

        $type = $_GET['type'] ? $_GET['type'] : 'add';
        $shop_type = ome_shop_type::get_shop_type();
        $shop_type['other'] = '其它平台';
        $shopObj = app::get('ome')->model('shop');
        $shop_rows = $shopObj->getlist();
        $shop_list = array();
        foreach($shop_rows as $shop){
            $shop_list[$shop['shop_id']] = $shop['name'];
        }
        $member_detail = array();
        if($member_id){
            $memberObj = app::get('ome')->model('members');
            $member_detail = $memberObj->getlist('*',array('member_id'=>$member_id));
            $member_detail = current($member_detail);
            $member_detail['shop_type_show'] = $shop_type[$member_detail['shop_type']];
            $shop_name = $member_detail['shop_id'] ? $shop_list[$member_detail['shop_id']] : '';
            $member_detail['shop_name'] = $shop_name;

        }

        $this->pagedata['oper_type']   =$type;
        $this->pagedata['finder_id']   =$finder_id;
        $this->pagedata['shop_type']   =$shop_type;
        $this->pagedata['shop_list']   =$shop_list;
        $this->pagedata['member_detail']   =$member_detail;
        unset($shop_type,$shop_list,$member_detail);
        $this->display("admin/member/add_customer.html");
    }


    function doAdd(){
       //uname
        $memberObj = app::get('ome')->model('members');
        $uname = $_POST['uname'];
        $filter['uname'] = $uname;
        if($_POST['shop_id']){
            $filter['shop_id'] = $_POST['shop_id'];
        }
        //表单验 证
        if ($_POST['zip'] && strlen($_POST['zip']) <> '6') {
            echo json_encode(array('rsp'=>'fail','msg'=>'请输入正确的邮编'));exit;
        }
        //固定电话与手机必填一项
        $gd_tel = str_replace(" ", "", $_POST['tel']);
        $mobile = str_replace(" ", "", $_POST['mobile']);
        $pattern = "/^400\d{7}$/";
        $pattern1 = "/^\d{1,4}-\d{7,8}(-\d{1,6})?$/i";
        if ($gd_tel) {
            $_rs = preg_match($pattern, $gd_tel);
            $_rs1 = preg_match($pattern1, $gd_tel);
            if ((!$_rs) && (!$_rs1)) {
                echo json_encode(array('rsp'=>'fail','msg'=>'请填写正确的固定电话号码'));exit;
            }
        }
        $pattern2 = "/^\d{8,15}$/i";
        if ($mobile) {
            if (!preg_match($pattern2, $mobile)) {
                echo json_encode(array('rsp'=>'fail','msg'=>'请输入正确的手机号码'));exit;
            }
            if ($mobile[0] == '0') {
                echo json_encode(array('rsp'=>'fail','msg'=>'手机号码前请不要加0'));exit;
            }
        }
        if($_POST['email'] && !strpos($_POST['email'],'@')) {
            echo json_encode(array('rsp'=>'fail','msg'=>'请输入正确的邮箱'));exit;
        }
        $member_detail = $memberObj->dump($filter,'member_id');

        if($member_detail && !in_array($_POST['oper_type'],array('edit'))){
            $data = array('rsp'=>'fail','msg'=>'会员名已存在!');

        }else{
            $member_info = $_POST;

            $area = explode(":",$_POST['contact']['area']);
            $area = explode("/",$area[1]);

            list($area_state,$area_city,$area_district) = $area;
            $member_info['area_state'] = $area_state;
            $member_info['area_city'] = $area_city;
            $member_info['area_district'] = $area_district;
            unset($_POST['contact']);
            $data = array('rsp'=>'succ','msg'=>'保存成功');
            $result = kernel::single('ome_member_func')->save($member_info,$_POST['shop_id']);

            if (!$result){
                $data = array('rsp'=>'fail','msg'=>'保存失败');
            }
        }

        echo json_encode($data);exit;
    }


    function del_address(){
        $address_id = intval($_GET['address_id']);
        $addressObj = app::get('ome')->model('member_address');
        $result = $addressObj->db->exec('DELETE FROM sdb_ome_member_address WHERE address_id='.$address_id);
        if($result){
            $data = array('rsp'=>'succ','msg'=>'删除成功');
        }else{
            $data = array('rsp'=>'fail','msg'=>'删除失败');
        }
        echo json_encode($data);
    }

    function edit_address(){
        $member_id = intval($_GET['member_id']);
        $addressObj = app::get('ome')->model('member_address');
        $address_detail = $addressObj->getList('*',array('member_id'=>$member_id));
        foreach($address_detail as &$address){
            $ship_area = $address['ship_area'] ? explode(':',$address['ship_area']) : '';

            if($ship_area){
                $address['ship_area'] = $ship_area[1];

            }

        }
        $finder_id = $_GET['finder_id'];
        $this->pagedata['finder_id'] = $finder_id;
        $this->pagedata['member_id'] = $member_id;
        $this->pagedata['address_detail'] = $address_detail;
        $this->singlepage("admin/member/address_detail.html");
    }

    function doEdit(){
        $data = $_POST;
        if(empty($data['address_id'])){
            unset($data['address_id']);
        }


        $addressObj = app::get('ome')->model('member_address');
        $addressObj->create_address($data);

        $data = array('rsp'=>'succ','msg'=>'编辑成功');
        echo json_encode($data);
    }

    function exportTemplate(){
        $memberObj = app::get('ome')->model('members');

        $title1 = $memberObj->io_title();

        kernel::single('omecsv_phpoffice')->export('客户导入模板-' . date('Ymd') . '.xlsx', [$title1]);

    }

    function getAddress(){
        $address_id = intval($_POST['address_id']);
        $addressObj = app::get('ome')->model('member_address');
        $address_detail = $addressObj->dump(array('address_id'=>$address_id),'*');
        $ship_area = $address_detail['ship_area'] ? explode(':',$address_detail['ship_area']) : '';
        if($ship_area){
            $address_detail['ship_area_text'] = $ship_area[1];
            $params = array('required'=>'true','value'=>$address_detail['ship_area'],'name'=>'ship_area');
            $regionhtml = kernel::single('eccommon_view_input')->input_region($params);
            $address_detail['regionhtml'] = $regionhtml;
        }
        echo json_encode($address_detail);
    }

    /**
     * ajax_get_shop
     * @return mixed 返回值
     */
    public function ajax_get_shop(){
        $shopex_shop_type = ome_shop_type::shopex_shop_type();
        $shop_type = $_POST['shop_type'];
        $str = '';
        if(in_array($shop_type,$shopex_shop_type)){
            $str = '<th><em class="c-red">*</em>来源店铺：</td><td><select id="shop_id" class=" x-input-select inputstyle" vtype="required" name="shop_id" >';

            $shopObj = app::get('ome')->model('shop');
            $shop_list = $shopObj->getlist('shop_id,name',array('shop_type'=>$shop_type));
            if($shop_list){
                foreach($shop_list as $shop){
                    $str .= '<option value='.$shop['shop_id'].'>'.$shop['name'].'</option>';
                }
            }

            $str .= '</select></td> ';
        }
        echo $str;
        exit;
    }

    function getDefaultAddress(){

        $params = array('id'=>'region','required '=>'true ','value'=>'','name'=>'ship_area');
        $regionhtml = kernel::single('eccommon_view_input')->input_region($params);
        echo json_encode(array('regionhtml'=>$regionhtml));
    }
    
    /**
     * 加密字段显示明文
     *
     * @return void
     * @author
     **/
    public function showSensitiveData($memberId, $fieldType = 'uname')
    {
        $memberInfo = app::get('ome')->model('members')->db_dump(array('member_id'=>$memberId), 'uname,mobile,tel,name,shop_id,shop_type,member_id,email');
        
        // 页面加密处理
        $memberInfo['encrypt_body'] = kernel::single('ome_security_router',$memberInfo['shop_type'])->get_encrypt_body($memberInfo, 'member', $fieldType);
        
        $this->splash('success',null,null,'redirect',$memberInfo);
    }
}
?>
