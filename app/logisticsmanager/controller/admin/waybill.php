<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_ctl_admin_waybill extends desktop_controller{
    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $params = array(
            'title'=>'快递面单管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
        );
        $this->finder('logisticsmanager_mdl_waybill', $params);


    }



    function findwaybill(){
        $billtype = $_GET['billtype'];
        $pre_title = '可用';
        $status = '0';
        switch ($billtype) {
            case 'active':
                $pre_title = '可用';
                $status = '0';
                break;
             case 'recycle':
                 $pre_title = '作废';
                 $status = '2';
                break;
             case 'used':
                 $pre_title = '已用';
                 $status = '1';
                break;
        }
        $params = array(
                        'title'=>$pre_title.'物流单号列表',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_filter'=>true,
                    );

        $params['base_filter']['status'] = $status;            
     	$params['base_filter']['channel_id'] = $_GET['channel_id'];
        
        $this->finder('logisticsmanager_mdl_waybill', $params);
    }

    /**
     * 获取EncryptPrintData
     * @return mixed 返回结果
     */
    public function getEncryptPrintData() {
        $logiNo         = trim($_POST['logi_no']);
        $batchLogiNo    = trim($_POST['batch_logi_no']);
        $deliveryId     = intval($_POST['delivery_id']);
        $channelId      = trim($_POST['channel_id']);
        $rs = kernel::single('erpapi_router_request')
                ->set('logistics',$channelId)
                ->electron_getEncryptPrintData([
                    'logi_no'       =>$logiNo,
                    'batch_logi_no' =>$batchLogiNo,
                    'delivery_id'   =>$deliveryId,
                    'custom_data'   => $_POST['custom_data']
                ]);
        echo json_encode($rs);
    }
}