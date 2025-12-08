<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 任务队列模型类
* @author 334395174@qq.com
* @version 0.1
*/
class financebase_mdl_queue extends dbeav_model
{

	public function getRow($cols='*',$filter=array())
	{
		$sql = "SELECT $cols FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        return $this->db->selectrow($sql);
	}


}