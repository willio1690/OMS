<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class financebase_data_bill_businesstype_abstract {
    protected $modelName;

    /**
     * doReconfirmTask
     * @param mixed $cursor_id ID
     * @param mixed $params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function doReconfirmTask($cursor_id,$params,&$errmsg) {
        $oFunc = kernel::single('financebase_func');
        $page_size = $oFunc->getConfig('page_size');

        $worker = get_class($this).".reconfirm";
        $mdlShopSettlement = app::get('financebase')->model($this->modelName);

        $total_num = $mdlShopSettlement->count($params);

        if($total_num > 0 ){
            $last_id = 0;

            $total_page = ceil($total_num/$page_size);

            for ($i=0; $i < $total_page; $i++) { 
                $filter['id|than'] = $last_id;
                $unique_id = array();
                $list = $mdlShopSettlement->getList('unique_id,id',$filter,0,$page_size,'id');
                if($list){
                    foreach ($list as $v) {
                        array_push($unique_id, $v['unique_id']);
                        $last_id = $v['id'];
                    }

                    $data = array();
                    $data['ids'] = $unique_id;
                    $oFunc->addTask('重新匹配对账单',$worker,$data);

                }
            }

        }
        return false;
    }

    protected function confirmItem($bill) {
        return false;
    }

    /**
     * reconfirm
     * @param mixed $cursor_id ID
     * @param mixed $params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function reconfirm($cursor_id,$params,&$errmsg)
    {
        $oFunc = kernel::single('financebase_func');

        $oFunc->writelog('支付宝明细-重新对账-开始','settlement',$params);

        if($params['ids'])
        {
            $mdlBill = app::get('financebase')->model('bill');

            $list = $mdlBill->getList('*',array('unique_id|in'=>$params['ids']));

            if($list)
            {
                foreach ($list as $v) {
                    $rs = $this->confirmItem($v);
                    if($rs['rsp'] == 'succ') {
                        $mdlBill->update(array('confirm_status'=>'1', 'split_status'=>'0', 'confirm_fail_msg'=>''), array('id'=>$v['id']));
                    } else {
                        $mdlBill->update(array('confirm_fail_msg'=>$rs['msg']), array('id'=>$v['id']));
                    }
                }
            }
        }
        
        $oFunc->writelog('支付宝明细-重新对账-完成','settlement','Done');

        return false;
    }

    /**
     * 获取SkuList
     * @param mixed $bill bill
     * @return mixed 返回结果
     */
    public function getSkuList($bill) {
        return array(array(), '没有对应方法');
    }

}