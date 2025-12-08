<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 收货地区
 */
class omeauto_order_label_address extends omeauto_order_label_abstract implements omeauto_order_label_interface
{
    /**
     * 设置已经创建好的配置内容
     * 
     * @param array $params
     * @return void
     */
    public function setRole($params)
    {
        $this->content = array();
        
        //转换为字符名称
        if (!empty($params) && is_array($params)) {
            $rows = kernel::database()->select('SELECT region_id, local_name FROM sdb_eccommon_regions WHERE region_id in (' . join(',', $params) . ')');
            foreach ($rows as $row)
            {
                $this->content[$row['region_id']] = $row['local_name'];
            }
        }
    }
    
    /**
     * 检查订单数据是否符合要求
     *
     * @param array $orderInfo
     * @param string $error_msg
     * @return bool
     */
    public function vaild($orderInfo, &$error_msg=null)
    {
        if(empty($this->content)){
            $error_msg = '没有设置收货地区规则';
            return false;
        }
        
        //检查收货人地区
        $ship_area = $orderInfo['consignee']['area'];
        if(empty($ship_area)){
            $error_msg = '订单没有收货人地区';
            return false;
        }
        
        //省、市、区、镇
        list(, $area, $region_id) = explode(':', $ship_area);
        $area = explode('/', $area);
        
        //有四级地区--镇
        if($area[3]){
            //读取三级区的region_id
            $sql = "SELECT * FROM sdb_eccommon_regions WHERE region_id=".intval($region_id);
            $regionRow = kernel::database()->selectrow($sql);
            $district_id = intval($regionRow['p_region_id']);
            
            //指定区域region_id是否在规则中(只需匹配到三级地区即可)
            if($this->content[$region_id] || $this->content[$district_id]){
                return true;
            }
        }else{
            //指定区域region_id是否在规则中
            if($this->content[$region_id]){
                return true;
            }
        }
        
        $error_msg = implode('-', $area) . ',不在设置的规则中';
        return false;
    }
}