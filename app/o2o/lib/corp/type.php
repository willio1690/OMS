<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店物料处理类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: type.php 2016-03-06 15:00
 */
class o2o_corp_type
{
    /**
     * 判断物流公司类型
     *
     * @param intval $corp_id
     * @param intval $mode 返回数据格式
     * @return array
     */
    public function get_corp_type($corp_id, $mode=false)
    {
        $corp_type      = array();
        $corpObj        = app::get('ome')->model('dly_corp');
        $corp_info      = $corpObj->dump(array('corp_id'=>$corp_id), 'type');
        
        if($corp_info['type'] == 'o2o_pickup')
        {
            if($mode)
            {
                $corp_type['type']    = 'o2o_pickup';
            }
            else 
            {
                $corp_type['o2o_pickup']    = true;
            }
        }
        elseif($corp_info['type'] == 'o2o_ship')
        {
            if($mode)
            {
                $corp_type['type']    = 'o2o_ship';
            }
            else
            {
                $corp_type['o2o_ship']    = true;
            }
        }
        
        return $corp_type;
    }
    
    /**
     * 获取门店相关物流类型
     *
     * @return array
     */
    public function get_corp_type_list()
    {
        $corp_type      = array();
        $corpObj        = app::get('ome')->model('dly_corp');
        $corp_info      = $corpObj->getList('type', array('d_type'=>2));
        if($corp_info)
        {
            foreach ($corp_info as $key => $val)
            {
                $corp_type[$val['type']]    = $val['type'];
            }
        }
        return $corp_type;
    }
}
