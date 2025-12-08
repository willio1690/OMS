<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_mdl_pick_bill_check_items extends dbeav_model
{

    public $order_label = [
        '0' => '未知',
        '1' => '普通订单',
        '2' => '原订单',
        '3' => '换订单',
        '4' => '二换订单',
        '5' => '未知订单',
    ];

    /**
     * 获取CheckList
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getCheckList($params = [])
    {
    	if (!$params['bill_id']) {
    		return [];
    	}
    	$filter = [
    		'bill_id' => $params['bill_id'],
    	];
    	if ($params['barcode_list']) {
    		$filter['barcode|in'] = $params['barcode_list'];
    	}
    	$list = $this->getList('*', $filter);
    	return $list;
    }

}
