<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 对账规则模型类
* @author 334395174@qq.com
* @version 0.1
*/
class finance_mdl_bill_category_rules extends dbeav_model
{

	public function getRow($cols='*',$filter=array())
	{
		$sql = "SELECT $cols FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        return $this->db->selectrow($sql);
	}

    /**
     * isExist
     * @param mixed $filter filter
     * @param mixed $rule_id ID
     * @return mixed 返回值
     */

    public function isExist($filter,$rule_id = 0)
	{
		$sql = "SELECT rule_id FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
		$rule_id and $sql.=" and rule_id <> ".$rule_id;
		return $this->db->selectrow($sql) ? true : false;
	}

    /**
     * 删除
     * @param mixed $filter filter
     * @param mixed $subSdf subSdf
     * @return mixed 返回值
     */
    public function delete($filter,$subSdf = 'delete'){
        if(parent::delete($filter)){
            foreach(kernel::servicelist('bill_category_rules_set') as $name=>$object){
                if(method_exists($object,'setRules')){
                    $object->setRules();
                }
            }
            return true;
        }else{
            return false;
        }
    }

}