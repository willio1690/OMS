<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_extend_filter_bs
{
    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        $betcMdl  = app::get('dealer')->model('betc');
        $betcList = [];
        foreach ($betcMdl->getList('*') as $k => $v) {
            $betcList[$v['betc_id']] = $v['betc_name'] . ' (' . $v['betc_code'] . ')';
        }

        $db['bs'] = array(
            'columns' => array(
                'betc_id' => array(
                    'type'          => $betcList,
                    'label'         => '贸易公司',
                    'filtertype'    => 'normal',
                    'filterdefault' => true,
                ),
            ),
        );

        return $db;
    }
}
