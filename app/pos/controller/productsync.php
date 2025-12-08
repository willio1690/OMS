<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_ctl_productsync extends desktop_controller{
    var $workground = "pos_center";
       function index(){
        $_GET['view'] = intval($_GET['view']);
        $finder_id = $_REQUEST['_finder']['finder_id'] ? : substr(md5($_SERVER['QUERY_STRING']), 5, 6);
        $pUrl='index.php?app=desktop&act=alertpages&goto='.urlencode('index.php?app=pos&ctl=productsync&act=findMaterial');
        $filter = array();
        $actions = [];
        $actions[] = array(
                    'label' => '货品分配',
                    'onclick' => <<<JS
javascript:Ex_Loader('modedialog',function() {new finderDialog('{$pUrl}',{params:{url:'index.php?app=pos&ctl=productsync&act=handwork_allot',name:'product_id[]'},onCallback:function(rs){MessageBox.success('分配成功');window.finderGroup['{$finder_id}'].refresh();}  });});
JS,
                 );
        //if(in_array($_GET['view'],array('1','2','3'))){
            $actions[] = array(
                    'label' => '批量同步',
                    'submit' => 'index.php?app=pos&ctl=productsync&act=syncDailog&org_id='.$_GET['org_id'],
                    'target'=>'dialog::{width:600,height:300,title:\'批量同步\'}"'
            );
            $actions[] = array(
                    'label' => '批量删除',
                    'submit' => 'index.php?app=pos&ctl=productsync&act=syncDelete&org_id='.$_GET['org_id'],
                    'target'=>'dialog::{width:600,height:300,title:\'批量删除\'}"'
            );
        //}
        $params = array(
            'title'=>app::get('pos')->_('基础物料分配'),
            'actions'=>$actions,
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_filter'=>true,
        );
       
        $this->finder('pos_mdl_syncproduct',$params);
         $html = <<<EOF
        <script>
              $$(".show_list").addEvent('click',function(e){
                 
                  var bm_id = this.get('bm_id');
                  var t_url ='index.php?app=pos&ctl=productprice&act=skuList&bm_id='+bm_id;
              var url='index.php?app=desktop&act=alertpages&goto='+encodeURIComponent(t_url);
        Ex_Loader('modedialog',function() {
            new finderDialog(url,{width:1000,height:660,
                
            });
        });
              });

        </script>
EOF;
        echo $html;exit;
    }

    function _views() {

        $syncMdl = app::get('pos')->model('syncproduct');
       
       
        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false);
        $sub_menu[1] = array('label' => app::get('base')->_('未同步'), 'filter' => array('sync_status'=>'0'), 'optional' => false);
        $sub_menu[2] = array('label' => app::get('base')->_('同步失败'),'filter' => array('sync_status' => '2'),'optional' => false);
        $sub_menu[3] = array('label' => app::get('base')->_('同步成功'),'filter' => array('sync_status' => '1'),'optional' => false);
        $sub_menu[4] = array('label' => app::get('base')->_('价格异常'),'filter' => array('price_status' => '1'),'optional' => false);
        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $v['addon'] ? $v['addon'] : $syncMdl->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=pos&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=' . $_GET['flt'] . '&view=' . $k . $s;
        }

        return $sub_menu;
    }
    

   

    //分配商品
    function dispatch(){
      

        $view = $_REQUEST['view'];
        $finder_id = $_REQUEST['finder_id'];
        $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
        $pagelimit = 50;
        $offset = ($page-1) * $pagelimit;
        $productsyncLib = kernel::single("pos_productsync");
        if($_REQUEST['search']){
          
            $params['search_key'] = $_REQUEST['search_key'];
            if(empty($_REQUEST['search_value'])){
                $params['search_value'] = $_REQUEST['search_value_'.$_REQUEST['search_key']];
                $this->pagedata['search_value_key'] = $params['search_value'];
            }else{
                $params['search_value'] = $_REQUEST['search_value'];
                $this->pagedata['search_value'] = $params['search_value'];
            }
            $search_filter = $productLib->get_filter($params,$offset,$pagelimit);
            if($search_filter === false){
                return false;
            }
            $data = $productsyncLib->get_goods_by_product_ids($search_filter);
            $count = $productsyncLib->get_goods_count_by_search($params);
            $link = 'index.php?app=pos&ctl=productsync&act=dispatch&view='.$view.'&search=true&search_value='.$params['search_value'].'&search_key='.$params['search_key'].'&target=container&page=%d';
            $this->pagedata['search_key'] = $params['search_key'];
            $this->pagedata['search_value_last'] = $params['search_value'];
        }else{
            $data = $productsyncLib->get_goods_by_wms($offset,$pagelimit);
            $count = $productsyncLib->get_goods_count_by_wms();
            $count = $count[0]['count'];
            $link = 'index.php?app=pos&ctl=productsync&act=dispatch&view='.$view.'&target=container&page=%d';
        }
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>$link,
        ));
        //获取自定义搜索选项
        $search = $productsyncLib->get_search_options();
        //获取自定义搜索项下拉列表
        $search_list = $productsyncLib->get_search_list();
  
        $this->pagedata['search'] = $search;
        $this->pagedata['count'] = $count;
        $this->pagedata['search_list'] = $search_list;
        $this->pagedata['rows'] = $data;
        $this->pagedata['pager'] = $pager;
        $this->pagedata['org_id'] = $org_id;
        $this->pagedata['finder_id'] = $finder_id;
        if($_GET['target'] || $_POST['search'] =='true'){
            return $this->display('admin/goodssync/index.html');
        }
        $this->singlepage('admin/goodssync/index.html');
    }

    function do_save(){
       
        $product_ids = $_POST['product_id'];

        $finder_id = $_POST['finder_id'];
        
        $productsyncLib = kernel::single("pos_productsync");
        $db = kernel::database();
        $limit = 50;//设置多少个货品组一个sql语句
        $this->begin();
        //全选时候的处理
        if($_POST['select_all'] == 'true'){
            $search_key = $_POST['search_key'];
            $search_value = $_POST['search_value'];
            if(!empty($search_key) && !empty($search_value)){
                $data = $productsyncLib->get_data_by_search($search_key,$search_value);
            }else{
                $data = $productsyncLib->get_goods_by_wms();
            }
        }else{
            $data = $productsyncLib->get_wms_goods($product_ids);
        }


        $sdf = $this->get_foreign_sku_sdf($data);

        if($all == 'true'){
            foreach($sdf as $value){
                foreach($value as $v){
                    $sql_find = "select bm_id from sdb_pos_syncproduct where material_bn = '".$v['material_bn']."'";
                    $rs = $db->select($sql_find);
                    if(!$rs){
                        $update_value[] = "('".$v['material_bn']."','".$v['bm_id']."','".$v['type']."')";
                    }
                }
            }
        }else{
            foreach($sdf as $value){
                $update_value[] = "('".$value['material_bn']."','".$value['bm_id']."','".$value['type']."')";
            }
        }
        $count = ceil(count($update_value) / $limit);
        $update_value_tmp = array_chunk($update_value, $limit,true);
        for($i=0;$i<$count;$i++){
            //插入数据
            $update_sql = "insert into sdb_pos_syncproduct (`material_bn`,`bm_id`,`type`) values ".implode($update_value_tmp[$i], ',');

            $db->exec($update_sql);
        }
        $this->end(true,'操作成功');
    }

    function get_foreign_sku_sdf($data){
        $sdf = array();
        foreach($data as $v){
            $sdf[] = array(
                'material_bn'   =>  $v['bn'],
                'bm_id'         =>  $v['product_id'],
                'type'           => $v['type'],
            );
        }
        return $sdf;
    }


   
    //商品同步
    function sync()
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $productsyncLib = kernel::single("pos_productsync");
        if ($_POST['filter']) {
            parse_str($_POST['filter'],$filter);unset($_POST['filter']);
            $_POST = array_merge((array)$_POST, (array)$filter);
            $_REQUEST = array_merge((array)$_REQUEST, (array)$filter);
        }

        if(empty($_REQUEST['id']) && $_REQUEST['isSelectedAll'] != '_ALL_') return NULL;
      
        $view =  $_POST['view'] ? intval($_POST['view']) : intval($_GET['view']);
   
        $wfsObj = app::get('pos')->model('syncproduct');
        $title = '货品同步';
        //全部选中处理
        if($_POST['isSelectedAll'] == '_ALL_'){

            //同步全部商品
            $productsyncLib->sync_all($_POST);
         
        }else{
            $id = $_REQUEST['id'];
            $foreign_list = $wfsObj->getlist('bm_id',array('id'=>$fsids));
            $product_ids = array_map('current',$foreign_list);

        }

        if(count($product_ids) > 2000){

            $product_ids_tmp = array_chunk($product_ids,2000,true);
            $count = ceil(count($product_ids)/2000);
            for($i=0;$i<$count;$i++){
              
                $productsyncLib->sync_all($product_ids_tmp[$i]);

            }
        }else{
           

         
          
           $productsyncLib->syncProduct_notifydata($product_sdf);
        }

        $this->splash('success','index.php?app=pos&ctl=productsync&act=index&view='.$view);
    }

    
    public function syncDailog()
     {

         $this->pagedata['request_url'] = 'index.php?app=pos&ctl=productsync&act=ajaxSync';
         $this->pagedata['autotime']    = '500';

         $_POST['sync_status'] =array('0','1','2');   
         $_POST = array_merge($_POST, $_GET);
        

         parent::dialog_batch('pos_mdl_syncproduct',true,100,'inc');
     }

     public function ajaxSync()
     {
         parse_str($_POST['primary_id'], $primary_id);

         if (!$primary_id) { echo 'Error: 请先选择数据';exit;}
        

         $retArr = array(
             'itotal'  => 0,
             'isucc'   => 0,
             'ifail'   => 0,
             'err_msg' => array(),
         );

         $syncproductMdl = app::get('pos')->model('syncproduct');
         $syncproductMdl->filter_use_like = true;
       
         $products = $syncproductMdl->getList('id,bm_id',$primary_id['f'],$primary_id['f']['offset'],$primary_id['f']['limit']);

         $retArr['itotal'] = count($products);

         foreach ($products as $v) {

           
             $rs = kernel::single('pos_event_trigger_goods')->add($v['id']);

             if ($rs) {
                 $retArr['isucc']++;
             } else {
                 $retArr['ifail']++;

                 
             }
         }

         echo json_encode($retArr),'ok.';exit;
    }
    
   public function syncDelete()
     {

         $this->pagedata['request_url'] = 'index.php?app=pos&ctl=productsync&act=ajaxSyncDelete';
         $this->pagedata['autotime']    = '500';

         $_POST['sync_status'] =array('0','1','2','3');   
         $_POST = array_merge($_POST, $_GET);
         if ($id) $_POST['id'] = $id;


         parent::dialog_batch('pos_mdl_syncproduct',true,10);
     }

     public function ajaxSyncDelete()
     {
         parse_str($_POST['primary_id'], $primary_id);

         if (!$primary_id) { echo 'Error: 请先选择数据';exit;}
        

         $retArr = array(
             'itotal'  => 0,
             'isucc'   => 0,
             'ifail'   => 0,
             'err_msg' => array(),
         );

         $syncproductMdl = app::get('pos')->model('syncproduct');
         $syncproductMdl->filter_use_like = true;

         $products = $syncproductMdl->getList('id,bm_id',$primary_id['f'],$primary_id['f']['offset'],$primary_id['f']['limit']);

         $retArr['itotal'] = count($products);

         foreach ($products as $v) {

           
             $rs = $syncproductMdl->db->exec("DELETE FROM sdb_pos_syncproduct WHERE id=".$v['id']."");

             if ($rs) {
                 $retArr['isucc']++;
             } else {
                 $retArr['ifail']++;

                
             }
         }

         echo json_encode($retArr),'ok.';exit;
    }

    public function handwork_allot()
    {
        $product_id = $_POST['product_id'];
       

        if (!$product_id) $this->splash('error',null,'请先选择商品');
      
        $data = array();
        if($product_id) {
            $products = app::get('material')->model('basic_material')->getList('bm_id,material_bn,type',array('bm_id'=>$product_id));
            foreach ($products as $key => $value) {
                $data[] = array(
                    'material_bn'       => $value['material_bn'],
                    'bm_id'             => $value['bm_id'],
                    'type'              => $value['type'],
                );
            }
        }
       

        if ($data) app::get('pos')->model('syncproduct')->replaceinsert($data);

        $this->splash();
    }
    
    public function findMaterial() {
        $this->view_source = 'dialog';

        //只能选择可售的物料
        $base_filter['visibled'] = 1;

        $base_filter['filter_sql'] = 'bm_id not in (select bm_id from sdb_pos_syncproduct)';

        $params = array(
            'title'                  => '基础物料列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'base_filter'            => $base_filter,
        );
        $this->finder('material_mdl_basic_material', $params);
    }

    
}
