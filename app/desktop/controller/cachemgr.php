<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_ctl_cachemgr extends desktop_controller 
{
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index() 
    {
        $this->pagedata['enable'] =
 		(get_class(cachemgr::instance()) == 'base_cache_nocache') ? 'false' : 'true';
 		if(cachemgr::status($msg)){
		   $this->pagedata['status']  = $msg; 
		}
		
		
        $this->page('cachemgr/index.html');
    }//End Function

    /**
     * status
     * @return mixed 返回值
     */
    public function status() 
    {  
		
        $this->pagedata['status'] = 'current';
        $this->index();
        if(cachemgr::status($msg)){
            $this->pagedata['status'] = $msg;
            // $this->display('cachemgr/status.html');
        }else{
            //echo '<p class="notice">'.(($msg) ? $msg : app::get('desktop')->_('无法查看状态')).'</p>';
        }
    }//End Function

    /**
     * optimize
     * @return mixed 返回值
     */
    public function optimize() 
    {
	
        $this->begin('');
		$this->end(cachemgr::optimize($msg),$msg);
    }//End Function
    
    /**
     * clean
     * @return mixed 返回值
     */
    public function clean() 
    {
	    $this->begin('');
		$this->end(cachemgr::clean($msg),$msg);
    }//End Function
}//End Class
