<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_ctl_rpcnotify extends desktop_controller{
    
    var $workground = 'desktop_ctl_system';
    var $limit = 20;

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app) 
    {
        if(defined('WITHOUT_DESKTOP_RPCNOTIFY') && constant('WITHOUT_DESKTOP_RPCNOTIFY')){
            die(app::get('desktop')->_('通知已被禁用'));
        }
        parent::__construct($app);
    }//End Function

    function index(){

        $this->finder('base_mdl_rpcnotify',array(
            'title'=>app::get('desktop')->_('消息通知'),
            'actions'=>array(
                            array('label'=>app::get('desktop')->_('标记为已读'), 'id'=>'id-rpcynotify-submit', 'submit'=>'index.php?ctl=rpcnotify&act=read'),
                        ),
            'use_buildin_recycle' => false,
            #'use_buildin_setcol' => false,
            #'use_buildin_tagedit' => false,
            ));
    }
    
    function get() {
        $filter = array();
        $arr = app::get('base')->model('rpcnotify')->getList( '*',$filter, 0, $this->limit );
        echo json_encode($arr);
    }
    
    
    
    /**
     * read
     * @return mixed 返回值
     */
    public function read() {
        if (!$_POST['from']) {
            $this->begin( kernel::router()->gen_url( array('app'=>'desktop','ctl'=>'rpcnotify','act'=>'index') ) );
        }else{
            $this->begin( kernel::router()->gen_url( array('app'=>'desktop','ctl'=>'dashboard','act'=>'index') ) );
        }
        
        $id = $_POST['id'];
        $is_selected_all = $_POST['isSelectedAll'];

        if( !$id && !$is_selected_all) 
            $this->end( false, app::get('desktop')->_('操作失败') );
        if ($id) {
            //桌面消息缓存
            $cacheInfo = cachecore::fetch('system_notice_data');
            foreach( (array)$id as $val ) {
                if ($cacheInfo) {
                   $infoKey = array_search($val, array_column($cacheInfo, 'id'));
                   $cacheInfo[$infoKey]['status'] = 'true';
                    cachecore::store('system_notice_data', $cacheInfo, 1800);
                }
                $data = array('status'=>'true','id'=>$val);
                $flag = app::get('base')->model('rpcnotify')->save( $data );
                if( $flag == false ) break;
            }
        }else {
            $data = array('status'=>'true');
            $filter = array();
            $flag = app::get('base')->model('rpcnotify')->update( $data, $filter );
        }
        $this->end( $flag, ($flag ? app::get('desktop')->_('操作成功') : app::get('desktop')->_('操作失败')) );
    }
}
