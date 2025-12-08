<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_delivery extends desktop_controller{
    var $name = "发货单";
    var $workground = "invoice_center";

    function index(){
        $filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress','succ')
        );

        if(isset($_POST['status']) && ($_POST['status']!='')){
            $filter['status'] = $_POST['status'];
        }

        $this->finder('ome_mdl_delivery',array(
            'title' => '发货单',
            'base_filter' => $filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }

    function reback(){
        $this->page('admin/delivery/reback_delivery.html');
    }

    function back(){
        $this->begin();
        if (empty($_POST['select_bn']) && empty($_POST['bn_select'])){
            $autohide = array();
            $this->end(false, '请输入正确的单号', '', $autohide);
        }

        $autohide = array('autohide'=>3000);

        $dlyObj  = app::get('wms')->model('delivery');
        $OiObj  = app::get('wms')->model('delivery_items');
        $dlyBillLib = kernel::single('wms_delivery_bill');


        if($_POST['select_bn'] == 'logi_no'){
            $select_type = 'logi_no';

            $delivery_id = $dlyBillLib->getDeliveryIdByPrimaryLogi($_POST['bn_select']);
            if(!$delivery_id){
                $delivery_id = $dlyBillLib->getDeliveryIdBySecondaryLogi($_POST['bn_select']);
            }

            $detail = $dlyObj->dump(array('delivery_id'=>$delivery_id));
        }elseif($_POST['select_bn']=='delivery_bn'){
            $select_type = 'delivery_bn';
            $detail = $dlyObj->dump(array('delivery_bn'=>$_POST['bn_select']));


        }

        $items = $OiObj->getList('*',array('delivery_id'=>$detail['delivery_id']));
        if(empty($detail)){
            $this->end(false, '没有该单号的发货单', '', $autohide);
        }

        $logi_no = $dlyBillLib->getPrimaryLogiNoById($detail['delivery_id']);
        $detail['logi_no'] = empty($logi_no) ? '' : $logi_no;
        if($detail['status'] == 1){
            $this->end(false, '该发货单已经被打回，无法继续操作', '', $autohide);
        }
        if($detail['delivery_logi_number'] > 0){
            $this->end(false, '该发货单已部分发货，无法继续操作', '', $autohide);
        }
        if($detail['status'] == 2){
            $this->end(false, '该发货单已暂停，无法继续操作', '', $autohide);
        }
        if($detail['status'] == 3){
            $this->end(false, '该发货单已经发货，无法继续操作', '', $autohide);
        }
        if($detail['type'] == 'reject'){
            $this->end(false, '该发货单是原样寄回的单子，无法继续操作', '', $autohide);
        }

        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        
        foreach($items as $k=>$value)
        {
            $barcode_val    = $basicMaterialBarcode->getBarcodeById($value['product_id']);
            
            $items[$k]['barcode'] = $barcode_val;
        }

        if((($detail['print_status'] & 1) == 1) || (($detail['print_status'] & 2) == 2 ) || (($detail['print_status'] & 4) == 4)){
            $this->pagedata['is_confirm'] = true;
        }

        $this->pagedata['select_type'] = $select_type;
        $this->pagedata['bn_select']   = $_POST['bn_select'];
        $this->pagedata['items']       = $items;
        $this->pagedata['detail']      = $detail;
        $this->page('admin/delivery/reback_delivery.html');
    }

    /**
     * 打回操作
     *
     */
    function doReback(){
        $autohide = array('autohide'=>3000);
        $this->begin('index.php?app=wms&ctl=admin_delivery&showmemo&p[0]='.$_POST['delivery_id']);
        if (empty($_POST['memo'])){
            $this->end(false, '备注请不要留空', '', $autohide);
        }

        $dlyObj  = app::get('wms')->model('delivery');
        $dlyProcessLib = kernel::single('wms_delivery_process');
        $opObj = app::get('ome')->model('operation_log');

        //$delivery_bn = $dlyObj->dump(array('delivery_id'=>$_POST['delivery_id']),'delivery_bn');
        //$logi_info = $delivery_bn['logi_no'] ;

        $dlyProcessLib->rebackDelivery($_POST['delivery_id'], $_POST['memo']);
        //$opObj->write_log('delivery_back@wms', $_POST['delivery_id'], '发货单打回');

        //如果安装拣货app，将拣货单状态设为取消
        if (app::get('tgkpi')->is_installed()) {
            $pickObj = app::get('tgkpi')->model('pick');
            $pickObj->update(array('pick_status'=>'cancel'),array('delivery_id'=>$_POST['delivery_id']));
        }
        $this->end(true, '操作成功', 'index.php?app=wms&ctl=admin_delivery&act=reback', $autohide);

    }

    /**
     * 填写打回备注
     *
     * @param bigint $dly_id
     */
    function showmemo($dly_id){
        $deliveryObj  = app::get('wms')->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'delivery_bn');
        $this->pagedata['delivery_id'] = $dly_id;
        $this->pagedata['delivery_bn'] = $dly['delivery_bn'];
        $this->display("admin/delivery/delivery_showmemo.html");
    }

       /**
        * 加密字段显示明文
        *
        * @return void
        * @author 
        **/
       public function showSensitiveData($delivery_id, $fieldType='')
       {
            // if (!kernel::single('desktop_user')->has_permission('sensitive_data_show')) {
            //     $this->splash('error',null,'您无权查看该数据');
            // }

            $deliveryMdl = app::get('wms')->model('delivery');

            $delivery = $deliveryMdl->db_dump($delivery_id,'shop_id,shop_type,ship_name,ship_tel,ship_mobile,ship_addr,delivery_id,delivery_bn,member_id,ship_province,ship_city,ship_district,memo,outer_delivery_bn');

            if ($delivery['member_id']) {
                $member = app::get('ome')->model('members')->db_dump($delivery['member_id'],'uname');

                $delivery['uname'] = $member['uname'];
            }

            $order_bns = kernel::single('ome_extint_order')->getOrderBns($delivery['outer_delivery_bn']);
            $delivery['order_bn'] = current($order_bns);
            // 处理加密
            $delivery['encrypt_body'] = kernel::single('ome_security_router',$delivery['shop_type'])->get_encrypt_body($delivery, 'delivery', $fieldType);

            // 推送日志
            // kernel::single('base_hchsafe')->order_log(array('operation'=>'查看发货单收货人信息','tradeIds'=>array($delivery['delivery_bn'])));
            
            //非拼多多丰密手机密文中间4位星号只对丰密模板处理
            if($delivery['shop_type'] != 'pinduoduo'){
                if($this->_isFm($delivery['logi_id'])){
                   $delivery['is_asterisk'] = true;
                }
            }
            $this->splash('success',null,null,'redirect',$delivery);
       }

       private function _isFm($logId){
          $flag          = false;
          $dlyCorpRes    = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$logId),'channel_id');
          $logChannelRes = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$dlyCorpRes['channel_id'],'channel_type'=>'sf'),'service_code');
          if($logChannelRes){
             $flag = true;
          } 
          
          return $flag;
       }

}
?>
