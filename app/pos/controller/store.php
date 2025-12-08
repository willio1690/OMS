<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_ctl_store extends desktop_controller
{
    
    
    function index() {
        
        $this->title='门店列表';
        $servers = kernel::single('pos_event_trigger_common')->getChannelId('pekon');
        $server_id = $servers['server_id'];
        $filter = $this->baseFilter();
        $_GET['view'] = intval($_GET['view']);
        $actions =[];

        //if(in_array($_GET['view'],array('1','2'))){
            $actions[] = array(
                'label' => '批量同步',
                'submit' => 'index.php?app=pos&ctl=store&act=syncstoreDailog',
                'target'=>'dialog::{width:600,height:300,title:\'批量同步\'}"'
            );
        //}
        $params = array(
            'title' => $this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_importxls'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'base_filter' => $filter,
            'actions'=>$actions,
              
        );
        $this->finder('pos_mdl_store', $params);
    }


    
    function _views() {

        $storeMdl = app::get('pos')->model('store');
       
        $base_filter = $this->baseFilter();
        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array_merge($base_filter), 'optional' => false);
        $sub_menu[1] = array('label' => app::get('base')->_('未同步'), 'filter' => array_merge($base_filter, array('sync_status'=>'0')), 'optional' => false);
        $sub_menu[2] = array('label' => app::get('base')->_('同步失败'),'filter' => array_merge($base_filter,array('sync_status' => '2')),'optional' => false);
        $sub_menu[3] = array('label' => app::get('base')->_('同步成功'),'filter' => array_merge($base_filter,array('sync_status' => '1')),'optional' => false);
        
        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $v['addon'] ? $v['addon'] : $storeMdl->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=pos&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=' . $_GET['flt'] . '&view=' . $k . $s;
        }

        return $sub_menu;
    }


    /**
     * syncstoreDailog
     * @return mixed 返回值
     */
    public function syncstoreDailog()
     {

         $this->pagedata['request_url'] = 'index.php?app=pos&ctl=store&act=ajaxSyncstore';
         $this->pagedata['autotime']    = '500';
         
         
         if ($_POST['isSelectedAll'] == '_ALL_') {

            $view = intval($_POST['view']);
               
            $subMenu           = $this->_views();
            $baseFilter        = $subMenu[$view]['filter'];
                   
            $_POST            = array_merge($baseFilter, $_POST);
            
        }else{
            $base_filter = $this->baseFilter();
            $_POST = array_merge($_POST, $_GET,$base_filter);
        }

        
         parent::dialog_batch('pos_mdl_store',true,10);
     }

    /**
     * ajaxSyncstore
     * @return mixed 返回值
     */
    public function ajaxSyncstore()
     {
         parse_str($_POST['primary_id'], $primary_id);

         if (!$primary_id) { echo 'Error: 请先选择数据';exit;}
        

         $retArr = array(
             'itotal'  => 0,
             'isucc'   => 0,
             'ifail'   => 0,
             'err_msg' => array(),
         );

         $storeMdl = app::get('pos')->model('store');
         $storeMdl->filter_use_like = true;

         $stores = $storeMdl->getList('store_id',$primary_id['f'],$primary_id['f']['offset'],$primary_id['f']['limit']);

         $retArr['itotal'] = count($stores);

         foreach ($stores as $v) {


             list($rs,$msg) = kernel::single('pos_event_trigger_shop')->add($v['store_id']);

             if ($rs) {
                 $retArr['isucc']++;
             } else {
                 $retArr['ifail']++;

                 $retArr['err_msg'] = sprintf('[%s]', $msg);
             }
         }

         echo json_encode($retArr),'ok.';exit;
    }

    /**
     * baseFilter
     * @return mixed 返回值
     */
    public function baseFilter(){
        $servers = kernel::single('pos_event_trigger_common')->getChannelId('pekon');
        $server_id = $servers['server_id'];
        $base_filter = [

            'server_id' =>  $server_id,
        ];
        return $base_filter;
    }
}