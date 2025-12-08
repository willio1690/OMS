<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_mdl_account extends dbeav_model{
 var $has_many = array(
        'account'=>'auth:append',
    );
var $subSdf = array(
        'delete' => array(
            'account:auth' => array('*'),
         )
    );
	
	/**
	 * 得到帐号用户名
	 */
	public function get_operactor_name($account_id='')
	{
		if ($account_id == '')
			return app::get('pam')->_('未知或无操作员');
		
		$tmp = $this->getList('login_name',array('account_id'=>$account_id));
		if (!$tmp)
		{
			return app::get('pam')->_('未知或无操作员');
		}
		
		return $tmp[0]['login_name'];
	}
}
