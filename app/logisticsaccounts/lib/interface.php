<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface logisticsaccounts_interface {

	public function get_branch_list();

	public function get_shop_list();
	public function get_api_logi_name_list() ;
	
	public function get_delivery_list();
}