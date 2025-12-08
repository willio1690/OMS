<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_extend_filter_betc
{
    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        $bbuMdl  = app::get('dealer')->model('bbu');
        $bbuList = [];
        foreach ($bbuMdl->getList('*') as $k => $v) {
            $bbuList[$v['bbu_id']] = $v['bbu_name'] . ' (' . $v['bbu_code'] . ')';
        }

        $db['betc'] = array(
            'columns' => array(
                'bbu_id' => array(
                    'type'          => $bbuList,
                    'label'         => '销售团队',
                    'filtertype'    => 'normal',
                    'filterdefault' => true,
                ),
            ),
        );

        return $db;
    }
}
