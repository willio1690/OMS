<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoexlib_command_blacklist extends base_shell_prototype {
	public $command_update = 'Update Blacklist';
    /**
     * command_update
     * @return mixed 返回值
     */
    public function command_update() {
		taoexlib_utils::update_blacklist();
	}
}