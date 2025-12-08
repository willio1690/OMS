<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_ctl_productprice extends desktop_controller{
    var $workground = "pos_center";
    function index(){
        $_GET['view'] = intval($_GET['view']);
        $finder_id = $_REQUEST['_finder']['finder_id'];

        $params = array(
            'title'=>app::get('pos')->_('物料价格列表'),
            'actions'=>array(
               
                 array(
                    'label' => '批量同步价格',
                    'submit' => 'index.php?app=pos&ctl=productprice&act=syncpriceDailog',
                    'target'=>'dialog::{width:600,height:300,title:\'批量同步价格\'}"'
                 ),
                
            ),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_filter'=>true,
        );
       
        $this->finder('pos_mdl_productprice',$params);

    }

    function _views(){
       if ($_GET['act'] != 'index') {
            return [];
        }
       $syncMdl = app::get('pos')->model('productprice');
       
       
        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false);
        $sub_menu[1] = array('label' => app::get('base')->_('BTQ'), 'filter' => array('store_sort'=>'BTQ'), 'optional' => false);
        $sub_menu[2] = array('label' => app::get('base')->_('TRADE'),'filter' => array('store_sort' => 'Trade'),'optional' => false);
        $sub_menu[3] = array('label' => app::get('base')->_('CRC'),'filter' => array('store_sort' => 'CRC'),'optional' => false);
        $sub_menu[4] = array('label' => app::get('base')->_('未同步'), 'filter' => array('sync_status'=>'0'), 'optional' => false);
        $sub_menu[5] = array('label' => app::get('base')->_('同步失败'),'filter' => array('sync_status' => '2'),'optional' => false);
        $sub_menu[6] = array('label' => app::get('base')->_('同步成功'),'filter' => array('sync_status' => '1'),'optional' => false);
        
        
        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $v['addon'] ? $v['addon'] : $syncMdl->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=pos&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=' . $_GET['flt'] . '&view=' . $k . $s;
        }

        return $sub_menu;
    }

   
   
    /**
     * syncpriceDailog
     * @return mixed 返回值
     */
    public function syncpriceDailog()
     {

         $this->pagedata['request_url'] = 'index.php?app=pos&ctl=productprice&act=ajaxSyncprice';
         $this->pagedata['autotime']    = '500';

         //$_POST['sync_status'] =array('1');   
         $_POST = array_merge($_POST, $_GET);
   


         parent::dialog_batch('pos_mdl_productprice',true,100,'inc');
     }

    /**
     * ajaxSyncprice
     * @return mixed 返回值
     */
    public function ajaxSyncprice()
     {
         parse_str($_POST['primary_id'], $primary_id);

         if (!$primary_id) { echo 'Error: 请先选择数据';exit;}
        

         $retArr = array(
             'itotal'  => 0,
             'isucc'   => 0,
             'ifail'   => 0,
             'err_msg' => array(),
         );

         $syncproductMdl = app::get('pos')->model('productprice');
         $syncproductMdl->filter_use_like = true;

         $products = $syncproductMdl->getList('*',$primary_id['f'],$primary_id['f']['offset'],$primary_id['f']['limit']);

         $retArr['itotal'] = count($products);

         list($rs,$msg) = kernel::single('pos_event_trigger_goods')->syncprice($products);
         $retArr['isucc']++;
         

         echo json_encode($retArr),'ok.';exit;
    }


    /**
     * test
     * @return mixed 返回值
     */
    public function test(){
        kernel::single('pos_autotask_timer_syncproduct')->process($params, $error_msg );
        echo '同步成功';
    }

    /**
     * skuList
     * @return mixed 返回值
     */
    public function skuList(){
       
        $pre_title = '可用';
        $bm_id = $_GET['bm_id'];
        
        $params = array(
            'title'=>'SKU价格列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
        );

        $params['base_filter']['bm_id'] = $_GET['bm_id'];

        $this->finder('pos_mdl_productprice', $params);
    }
}
