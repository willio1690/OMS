<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_billconfirm{

    /**
     * 获取账单信息
     * @access public
     * @param int $confirm_id 账单ID
     * @return Array
     */
    function dump($confirm_id=''){
        if (empty($confirm_id)) return NULL;

        $confirmModel = &app::get('finance')->model('bill_confirm');
        $filter = array('confirm_id'=>$confirm_id);
        $detail = $confirmModel->getList('*',$filter,0,1);
        return $detail[0];
    }

    /**
     * 无归属账单作废
     * @access public
     * @param mixed $filter 作废条件
     * @return bool
     */
    function cancel($filter){
        if (empty($filter)) return true;
        
        if (isset($filter['isSelectedAll']) && $filter['isSelectedAll'] == '_ALL_'){
            return $this->batch_delete($filter);
        }else{
            return $this->delete($filter);
        }
    }

    /**
     * 删除无归属账单
     * @access public
     * @param mixed $confirm_id 账单ID
     * @return bool
     */
    function delete($confirm_id){
        if (empty($confirm_id)) return true;

        $confirmModel = &app::get('finance')->model('bill_confirm');
        $filter = array('confirm_id'=>$confirm_id);
        return $confirmModel->delete($filter);
    }

    /**
     * 批量删除无归属账单
     * @access public
     * @param Array $filter 删除条件
     * @return bool
     */
    function batch_delete($filter=''){

        $confirmModel = &app::get('finance')->model('bill_confirm');
        $confirmModel->filter_use_like = true;
        return $confirmModel->delete($filter);
    }

}