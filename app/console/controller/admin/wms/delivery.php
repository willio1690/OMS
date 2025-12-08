<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/10/18 17:14:52
 * @describe: 控制器
 * ============================
 */
class console_ctl_admin_wms_delivery extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[]  =array('label' => '重试回传结果',
            'submit' => 'index.php?app=console&ctl=admin_wms_delivery&act=batch_sync',
            'confirm' => '你确定要对勾选的单据重试吗？',
            'target' => 'refresh'
        );
        
        $params = array(
                'title'=>'第三方发货单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('console_mdl_wms_delivery', $params);
    }
    
    /**
     * batch_sync
     * @return mixed 返回值
     */
    public function batch_sync()
    {
        $wdMdl = app::get('console')->model('wms_delivery');
        
        // $this->begin('');
        
        //check
        if($_POST['isSelectedAll'] == '_ALL_'){
            $this->splash('error', null,'不支持全选');
        }
        
        $ids = $_POST['id'];
        if(empty($ids)){
            $this->splash('error', null, '无效的操作请求');
        }
        
        if(count($ids) > 100){
            $this->splash('error', null, '每次最多只能操作100单');
        }
        
        $deliveryList = $wdMdl->getList('id,delivery_id,delivery_bn,delivery_status', array('id'=>$ids));
        $deliveryList = array_column($deliveryList, null, 'id');
        
        //exec
        foreach ($ids as $id)
        {
            $deliveryInfo = $deliveryList[$id];
            
            //check
            if($deliveryInfo['delivery_status'] == '3'){
                $this->end(false, '发货单号：'. $deliveryInfo['delivery_bn'] .'已经是完成状态,不能重复请求!');
            }
            
            kernel::single('console_wms_delivery')->retryWmsDelivery($id);
        }
        
        $this->splash('success', null, '命令已经被成功发送！！');
    }
}