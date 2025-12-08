<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class purchase_messenger_tmpl{
    
    /**
     * last_modified
     * @param mixed $tplname tplname
     * @return mixed 返回值
     */
    public function last_modified($tplname) 
    {
        $systmpl = app::get('ome')->model('print_tmpl_diy');
       $aRet = $systmpl->getList('*',array('active'=>'true','app'=>'purchase','tmpl_name'=>$tplname));
        if($aRet){
              return $aRet[0]['edittime'];    
        }
        return time();
    }

    /**
     * 获取_file_contents
     * @param mixed $tplname tplname
     * @return mixed 返回结果
     */
    public function get_file_contents($tplname) 
    { 
       $systmpl = app::get('ome')->model('print_tmpl_diy');
       $aRet = $systmpl->getList('*',array('active'=>'true','app'=>'purchase','tmpl_name'=>$tplname));
        if($aRet){
              return $aRet[0]['content'];    
        }
        return null;
        
    }

}
?>