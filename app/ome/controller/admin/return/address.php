<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_return_address extends desktop_controller
{
    var $workground = "setting_tools";
    
    function index()
    {
        $params = array(
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
        );
        
        $params['title'] = '平台店铺售后地址库';
        $params['actions'] = array(
            array(
                'label'  => '同步平台地址库',
                'href'   => 'index.php?app=ome&ctl=admin_return_address&act=selectShop&shop_id=' . $_GET['shop_id'],
                'target' => "dialog::{width:600,height:300,title:'同步平台店铺售后地址库'}",
            ),
            array(
                'label'  => '添加',
                'href'   => $this->url . '&act=edit',
                'target' => "dialog::{width:550,height:400,title:'添加退货地址'}",
            )
        );
        
        $this->finder('ome_mdl_return_address', $params );
    }
    
    /**
     * edit
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function edit($id=0) {
        if($id) {
            $this->pagedata['data'] = app::get('ome')->model('return_address')->db_dump($id);
        }
        $oShop = $this->app->model('shop');
        $shop_list = $oShop->getlist('*',array('shop_type'=>array('xhs','taobao','youzan')));
        if ($shop_list) {
            foreach ($shop_list as $k=>$shop ) {
                if ($shop['node_id'] == '') {
                    unset($shop_list[$k]);
                }
            }
        }

        $this->pagedata['select_shop_id'] = $_GET['shop_id'];
        $this->pagedata['shop'] = $shop_list;
        $this->display('admin/refund/address.html');
    }
    
    function doAddAddress(){
        $this->begin($this->url);
        $post = $_POST;
        $shop_id = $post['shop_id'];
        $shopObj = app::get('ome')->model('shop');
        $shop_detail = $shopObj->dump(array('shop_id'=>$shop_id),'shop_type');

        //新增地址
        $area = $post['contact']['area'];
        if (strpos($area, ":")){
            $area = explode(":", $area);
            $area = explode("/", $area[1]);
        }
    
        $address_data = array(
            'shop_id'      => $shop_id,
            'shop_type'    => $shop_detail['shop_type'],
            'contact_name' => $post['contact']['contace_name'],
            'province'     => $area[0],
            'city'         => $area[1],
            'country'      => $area[2],
            'addr'         => $post['contact']['addr'],
            'mobile_phone' => $post['contact']['mobile'],
            'address_type' => '1',
            'add_type'     => 'manual',
            'cancel_def'   => $post['is_default'],
            'area'         => $post['contact']['area'],
        );
        if ($post['address_id']) {
            $address_data['address_id'] = $post['address_id'];
        }
        $addressObj = app::get('ome')->model('return_address');
        if ($post['is_default']) {
            $addressObj->update(array('cancel_def'=>'false'),array('shop_id'=>$shop_id));
        }
        if ($addressObj->save($address_data)) {
            
            $this->end(true, '操作完成');
        }else{
            $this->end(false, '操作失败');
        }
        
        
    }
    /**
     * 获取地址库
     * @param
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function selectShop()
    {
        $oShop = $this->app->model('shop');
        $shop_list = $oShop->getlist('*',array('shop_type'=>array('taobao','360buy','luban','dewu')));
        if ($shop_list) {
            foreach ($shop_list as $k=>$shop ) {
                if ($shop['node_id'] == '') {
                    unset($shop_list[$k]);
                }
            }
        }
        
        $this->pagedata['select_shop_id'] = $_GET['shop_id'];
        $this->pagedata['shop'] = $shop_list;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/return_product/get_return_address.html');
    }
    
    /**
     * 获取地址
     * @param   shop_id
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getAddress()
    {
        $this->begin();
        $shop_id = $_POST['shop_id'];
        if ($shop_id=='') {
            $this->end(false,'请选择店铺');
        }
        $rs = kernel::single('ome_service_aftersale')->searchAddress($shop_id,'');
        
        $this->end(true,'获取成功');
    }

    /*
     * 通过id获取地址
     */
    function getAddressById()
    {
        $address_id = $_POST['id'];
        if ($address_id){
            $oAddress = $this->app->model('return_address');
            $data = $oAddress->dump(array('address_id'=>$address_id));
            $phone = explode('-',$data['phone']);#将电话处理一下
            $tmp = array(
                'contact_id'    =>$data['contact_id'],
                'address'       =>$data['province'].$data['city '].$data['country'].$data['addr'],
                'zip_code'      =>$data['zip_code'],
                'contact_name'  =>$data['contact_name'],
                'phone'         =>$phone[0].$phone[1],
                'mobile_phone'  =>$data['mobile_phone'],
            );
            
            echo json_encode($tmp);
        }
    }

    function findAddress()
    {
        $params = array(
            'title'=>'地址列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );
        
        $this->finder('ome_mdl_return_address', $params);
    }
    
    /**
     * Ajax获取平台售后退货地址库
     */
    public function ajaxGetAddress()
    {
        $shopObj = app::get('ome')->model('shop');
        
        //init
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'total' => 0,
            'err_msg' => array(),
        );
        
        $shop_id = trim($_POST['shopId']);
        if (empty($shop_id)) {
            $retArr['err_msg'] = array('请先选择店铺');
            echo json_encode($retArr);
            exit;
        }
        
        //店铺信息
        $shopInfo = $shopObj->dump(array('shop_id'=>$shop_id), '*');
        if(empty($shopInfo) || empty($shopInfo['node_id'])) {
            $retArr['err_msg'] = array('店铺信息不存在或者店铺未绑定');
            echo json_encode($retArr);
            exit;
        }
        $shop_type = $shopInfo['shop_type'];
        
        //page
        $_POST['nextPage'] = intval($_POST['nextPage']);
        $page = empty($_POST['nextPage']) ? 1 : $_POST['nextPage'];
        
        //request
        $search_type = '';
        $result = kernel::single('ome_service_aftersale')->searchAddress($shop_id, '', $page);
        
        //[兼容]其它平台异步拉取
        if($result === true){
            $retArr['itotal'] = 1;
            $retArr['ifail'] = 0;
            $retArr['total'] = 1;
            $retArr['next_page'] = 0;
            
            echo json_encode($retArr);
            exit;
        }
        
        //result
        if($result['rsp'] == 'succ') {
            $next_page = $page + 1;
            $data = $result['data'];
            if(is_string($data)){
                $data = json_decode($data, true);
            }
            
            $total = intval($data['total']);
            
            //[兼容]其它平台只支持一次拉取所有数据(抖音平台支持分页拉取)
            if(!in_array($shop_type, array('luban'))){
                $next_page = 0;
            }
            
            //request
            if(empty($data) || empty($data['address_list'])){
                //没有可拉取的内容
                $retArr['itotal'] = $total;
                $retArr['ifail'] = 0;
                $retArr['total'] = $total;
                $retArr['next_page'] = 0;
            }else{
                //继续拉取下一页
                $page_no = intval($data['page_no']);
                $page_no = ($page_no ? $page_no : $page);
                
                $page_size = intval($data['page_size']);
                
                $itotal = ($page_no * $page_size);
                $itotal = ($itotal > $total ? $total : $itotal);
                
                //是否继续拉取下一页
                if($itotal >= $total){
                    $next_page = 0;
                }
                
                //ret
                $retArr['itotal'] += $itotal;
                $retArr['ifail'] += 0;
                $retArr['total'] = $total;
                $retArr['next_page'] = $next_page;
            }
        }else{
            $retArr['err_msg'] = array($result['err_msg']);
        }
        
        echo json_encode($retArr);
        exit;
    }
}
?>