<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_monthend_uncharge extends desktop_controller{


    /**
     * index
     * @param mixed $monthly_id ID
     * @return mixed 返回值
     */
    public function index($monthly_id){

        $mdlMonthlyReport = $this->app->model('monthly_report');
        $this->report = $mdlMonthlyReport->getList('shop_id,bill_in_amount,bill_out_amount,ar_in_amount,ar_out_amount,begin_time,end_time,monthly_date,monthly_id',array('monthly_id'=>$monthly_id),0,1);

        if(!$this->report) exit('Hack Attack');

        $this->report = $this->report[0];

        $base_filter = array('channel_id'=>$this->report['shop_id'],'status'=>0,'monthly_id|lthan'=>$monthly_id);

        $params = array(
            'title'=>'往期单据',
            'actions'=>array(),
            'use_buildin_export'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_view_tab'=>true,
            'use_buildin_selectrow'=>false,
            'use_buildin_filter'=>true,
            'orderBy'=> 'ar_id desc',
            'base_filter' => $base_filter,
            'finder_cols'=>'column_edit,ar_bn,channel_name,trade_time,member,type,order_bn,column_sale_money,column_fee_money,money,status,confirm_money,unconfirm_money,charge_status,charge_time,monthly_status,column_delete',
        );
        $this->finder('finance_mdl_uncharge_ar',$params);
    }


    /**
     * 重置
     * @param mixed $ar_id ID
     * @param mixed $monthly_id ID
     * @return mixed 返回值
     */
    public function reset($ar_id,$monthly_id)
    {
        $mdlBillAr = app::get('finance')->model('ar');
        $mdlMonthlyReport = $this->app->model('monthly_report');


        $info = $mdlBillAr->getList('channel_id',array('ar_id'=>$ar_id,'status'=>0),0,1);
        $info = $info[0];

        $monthly_report = $mdlMonthlyReport->getList('monthly_id,monthly_date',array('shop_id'=>$info['channel_id'],'status'=>1));

        $this->pagedata['monthly_report'] = $monthly_report;
        $this->pagedata['ar_id'] = $ar_id;
        $this->pagedata['monthly_id'] = $monthly_id;

        $this->singlepage('monthed/reset_uncharge.html');
    }

    /**
     * doSet
     * @return mixed 返回值
     */
    public function doSet()
    {
        $this->begin('index.php?app=finance&ctl=monthend_uncharge&act=index&p[0]='.$_POST['monthly_id']);
        $mdlBillAr = app::get('finance')->model('ar');
        $ar_info = $mdlBillAr->getList('ar_bn',array('ar_id'=>intval($_POST['ar_id'])),0,1);
        $ar_info = $ar_info[0];
 
        $op_name = kernel::single('desktop_user')->get_name();
        if($mdlBillAr->update(array('monthly_id'=>intval($_POST['monthly_id']),'charge_status'=>1,'charge_time'=>time()),array('ar_id'=>intval($_POST['ar_id']))))
        {
            finance_func::addOpLog($ar_info['ar_bn'],$op_name,'重新入账','调账');
            $this->end(true, app::get('base')->_('更改成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('更改失败'));
        }
    }



}