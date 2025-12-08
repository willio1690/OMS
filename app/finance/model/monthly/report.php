<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_monthly_report extends dbeav_model{
    
    function modifier_status($row){
        $status = array('未启用','未关账','已关账');
        return $status[$row];
    }


    function getListByTime($begin_time)
    {
        return $this->db->select('select shop_id from sdb_finance_monthly_report where begin_time = '.$begin_time);
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){

        if(isset($filter['shop_id']))
        {
            $shop_info = app::get("ome")->model("shop")->getList('shop_id',array('name'=>$filter['shop_id']),0,1);
            if($shop_info)
            {
            	$filter['shop_id'] = $shop_info[0]['shop_id'];
            }

        }

        return parent::_filter($filter, $tableAlias, $baseWhere);
    }


    /**
     * 获取ShopList
     * @return mixed 返回结果
     */
    public function getShopList()
    {
        $res = $this->db->select('select report.shop_id,shop.name from sdb_finance_monthly_report as report left join sdb_ome_shop as shop on report.shop_id = shop.shop_id group by report.shop_id');
        return $res;
    }
}
