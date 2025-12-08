<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_productsync extends desktop_controller{
    
    var $workground = "o2o_center";
       function index(){
        $_GET['view'] = intval($_GET['view']);
        $finder_id = $_REQUEST['_finder']['finder_id'] ? : substr(md5($_SERVER['QUERY_STRING']), 5, 6);
        $pUrl='index.php?app=desktop&act=alertpages&goto='.urlencode('index.php?app=o2o&ctl=admin_productsync&act=findMaterial');
        $filter = array();
        $actions = [];
        $actions[] = array(
                    'label' => '货品分配',
                    'onclick' => <<<JS
javascript:Ex_Loader('modedialog',function() {new finderDialog('{$pUrl}',{params:{url:'index.php?app=o2o&ctl=admin_productsync&act=handwork_allot',name:'product_id[]'},onCallback:function(rs){MessageBox.success('分配成功');window.finderGroup['{$finder_id}'].refresh();}  });});
JS,
                 );
        //if(in_array($_GET['view'],array('1','2','3'))){
            
            $actions[] = array(
                    'label' => '批量删除',
                    'submit' => 'index.php?app=o2o&ctl=admin_productsync&act=syncDelete&org_id='.$_GET['org_id'],
                    'target'=>'dialog::{width:600,height:300,title:\'批量删除\'}"'
            );
        //}
        $params = array(
            'title'=>app::get('pos')->_('门店销售分配'),
            'actions'=>$actions,
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_filter'=>true,
        );
       
        $this->finder('o2o_mdl_syncproduct',$params);
        
    }


    
   public function syncDelete()
     {

         $this->pagedata['request_url'] = 'index.php?app=o2o&ctl=admin_productsync&act=ajaxSyncDelete';
         $this->pagedata['autotime']    = '500';

         $_POST['sync_status'] =array('0','1','2','3');   
         $_POST = array_merge($_POST, $_GET);
         if ($id) $_POST['id'] = $id;


         parent::dialog_batch('o2o_mdl_syncproduct',true,10);
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

         $syncproductMdl = app::get('o2o')->model('syncproduct');
         $syncproductMdl->filter_use_like = true;

         $products = $syncproductMdl->getList('id,bm_id',$primary_id['f'],$primary_id['f']['offset'],$primary_id['f']['limit']);

         $retArr['itotal'] = count($products);

         foreach ($products as $v) {

           
             $rs = $syncproductMdl->db->exec("DELETE FROM sdb_o2o_syncproduct WHERE id=".$v['id']."");

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
       

        if ($data) app::get('o2o')->model('syncproduct')->replaceinsert($data);

        $this->splash();
    }
    
    public function findMaterial() {
        $this->view_source = 'dialog';

        //只能选择可售的物料
        $base_filter['visibled'] = 1;

        $base_filter['filter_sql'] = 'bm_id not in (select bm_id from sdb_o2o_syncproduct)';

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
