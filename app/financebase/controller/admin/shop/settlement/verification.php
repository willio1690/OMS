<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 核销误差控制层
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_verification extends desktop_controller
{

	// 违禁词列表
    /**
     * error
     * @return mixed 返回值
     */

    public function error()
	{

        $use_buildin_export    = false;
        $use_buildin_import    = false;
        

        
        $base_filter = array();
        $actions = array(
                    array('label'=>'新增','href'=>'index.php?app=financebase&ctl=admin_shop_settlement_verification&act=setVerificationError&p[0]=0&singlepage=false&finder_id='.$_GET['finder_id'],'target'=>"dialog::{width:550,height:400,resizeable:false,title:'核销规则添加'}"),
                );



        $params = array(
            'title'=>'核销规则设置',
            'actions' => $actions,
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>false,
            'use_buildin_export'=> $use_buildin_export,
            'use_buildin_import'=> $use_buildin_import,
     
        );

        $this->finder('financebase_mdl_bill_verification_error',$params);
    }


    // 设置具体类别名称
    /**
     * 设置VerificationError
     * @param mixed $id ID
     * @return mixed 返回操作结果
     */
    public function setVerificationError($id=0)
    {
        $shop_info = array('shop_id'=>'','money'=>0,'id'=>0);
        
        if($id)
        {
            $oVerificationError = app::get('financebase')->model("bill_verification_error");
            $shop_info=$oVerificationError->dump($id);
            // $shop_info['money'] = sprintf("%.2f",$shop_info['money']);
        }

        if (!$shop_info['rule']) {
            $shop_info['rule'] = array ( 0 => array ());
        }

        $this->pagedata['shop_list'] = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['shop_info'] = $shop_info;
        $this->display("admin/verification/error.html");
    }

    // 保存具体类别名称
    /**
     * 保存VerificationError
     * @return mixed 返回操作结果
     */
    public function saveVerificationError()
    {
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_verification&act=error');

        $oVerificationError = app::get('financebase')->model("bill_verification_error");
        
        $data = array();
        $data['id']        = intval($_POST['id']);
        $data['money']     = sprintf("%.2f",$_POST['money']);
        $data['shop_id']   = $_POST['shop_id'];
        $data['name']      = trim($_POST['name']);
        $data['priority']  = trim($_POST['priority']);
        $data['is_verify'] = $_POST['is_verify'];
        $data['verify_mode'] = $_POST['verify_mode'];
        $data['rule']      = $_POST['rule'];

        if (!$data['id']) $data['create_time'] = time();

        // 是否唯一
        if($oVerificationError->isExist(array('name'=>$data['name'],'shop_id' => $data['shop_id']),$data['id']))
        {
            $this->end(false, "误差类型已经存在");
        }

        if($oVerificationError->save($data))
        {
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }


   


}