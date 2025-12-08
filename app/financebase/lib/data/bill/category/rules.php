<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 具体规则类
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_data_bill_category_rules 
{


    // 读取规则 
    /**
     * 获取Rules
     * @param mixed $type type
     * @return mixed 返回结果
     */

    public function getRules($type='alipay')
    {
        $key = 'bill.rules.'.$type;
    	$list = app::get('financebase')->getConf($key);
        if($list){
            return $list;
        }else{
            $data = $this->setRules();
            if(isset($data[$type])) return $data[$type];
            return array();
        }
    }

    /**
     * 设置Rules
     * @return mixed 返回操作结果
     */
    public function setRules()
    {

        $list = app::get('financebase')->model('bill_category_rules')->getList('bill_category,rule_content,rule_id',array(),0,-1,'ordernum');

        $rule_ref = array();

        $res = array();
       
        if($list)
        {
            foreach ($list as $k=>$v) 
            {
                $rule_ref[$v['bill_category']] = $v['rule_id'];
                if(!$v['rule_content']) continue;
                $v['rule_content'] = json_decode($v['rule_content'],1);

                
                foreach ($v['rule_content'] as $type => $item) {
                    $tmp = array();
                    foreach ($item as $k2 => $v2) 
                    {
                        foreach ($v2 as $rule) 
                        {
                            $tmp[$k2][$rule['rule_op']][] = $rule;
                        }
                    }

                    $res[$type][] = array('bill_category'=>$v['bill_category'],'rule_content'=>$tmp);
                }
          
            }
        }

        //规则关联
        app::get('financebase')->setConf('bill.rules.ref',$rule_ref);
        if($res){
            foreach ($res as $type => $rules) {
                app::get('financebase')->setConf('bill.rules.'.$type,$rules);
            }
        } else {
            app::get('financebase')->setConf('bill.rules.alipay',array ());
            app::get('financebase')->setConf('bill.rules.360buy',array ());
        }

        return $res;
    }

  

}