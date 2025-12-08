<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_ctl_admin_gift extends desktop_controller{

    var $workground = 'channel_center';
    

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){

        $title = '赠品管理';

        $this->finder('crm_mdl_gift',array(
                'title'=>$title,
                'actions'=>array(             
                        array('label'=>app::get('crm')->_('新增'),'target'=>'dialog::{title:\'新增赠品\',width:\'400px\',height:\'300px\'}','href'=>$this->url."&act=addGift"),
                        array('label'=>app::get('crm')->_('删除'),'icon' => 'del.gif', 'confirm' =>'确定删除选中项？','submit'=>'index.php?app=crm&ctl=admin_gift&act=delGift',),
                ),
                'use_buildin_recycle'=>false,
                'orderBy' =>'gift_id DESC',
        ));
    }
    

    /**
     * 添加Gift
     * @return mixed 返回值
     */
    public function addGift(){
        $this->display('admin/gift/add.html');
    }

    /**
     * 获取CrmInfo
     * @return mixed 返回结果
     */
    public function getCrmInfo(){

        $channelObj = app::get('channel')->model('channel');

        $filter = array('channel_type'=>'crm','filter_sql'=>'(node_id is not null and node_id !="")');

        $crmdata = $channelObj->getChannelInfo('count(channel_id) as _count',$filter);

        return $crmdata[0]['_count'];
    }
    
    #构造一个商品列表页面
    /**
     * 获取ProductInfo
     * @return mixed 返回结果
     */
    public function getProductInfo(){
        
            $base_filter['visibility'] = 'true';
            $base_filter['product_id|notin'] = explode(',',$product_id);

            $params = array(
               'title'=>'商品列表',
               'base_filter' => $base_filter,
               'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_setcol'=>true,
                'use_buildin_refresh'=>true,
                'orderBy' =>'product_id DESC',
                'alertpage_finder'=>true,
                'use_view_tab' => false,
            );
            return $this->finder('ome_mdl_products',$params);
    }

    function myfinder($object_name,$params){

         header("cache-control: no-store, no-cache, must-revalidate");

        $finder = kernel::single('crm_html',$this);

        foreach($params as $k=>$v){
            $finder->$k = $v;
        }
        $app_id = substr($object_name,0,strpos($object_name,'_'));
        $app = app::get($app_id);
        $finder->app = $app;
        $finder->work($object_name);    
    }

    /**
     * 保存Gift
     * @return mixed 返回操作结果
     */
    public function saveGift(){
        $sm_ids = $this->_request->get_post('sm_id');
    
        if(empty($sm_ids)){
            $this->splash('error', $this->url.'&act=index', '缺少物料');
        } 
       
        $giftObj = $this->app->model('gift');
        $salesMObj = app::get('material')->model('sales_material');

        $gifts = $giftObj->getList('gift_id,product_id',array('product_id|in'=>$sm_ids));
        foreach($gifts as $v){
             $gift[$v['product_id']] = $v['gift_id'];
        }

        $salesMs = $salesMObj->getList('sm_id,sales_material_bn,sales_material_name',array('sm_id|in'=>$sm_ids,'sales_material_type'=>'3'));

        $data = array();
        foreach((array)$salesMs as $k=>$salesM){
            $data[$k]['product_id'] = $salesM['sm_id'];
            $data[$k]['gift_bn'] = $salesM['sales_material_bn'];
            $data[$k]['gift_name'] = $salesM['sales_material_name'];
            $data[$k]['gift_id'] = $gift[$salesM['sm_id']];
            $data[$k]['status'] = 1;
            $giftObj->save($data[$k]);
        }

        $this->splash('success', $this->url.'&act=index', '操作成功');
    }

    /**
     * delGift
     * @return mixed 返回值
     */
    public function delGift(){
       $this->begin('index.php?app=crm&ctl=admin_gift&act=index');
       $giftObj = $this->app->model('gift');
       $isSelectedAll = $this->_request->get_post('isSelectedAll');
       $giftids = $this->_request->get_post('gift_id');

       if($isSelectedAll != '_ALL_' && $giftids){
           $gift_id = array('gift_id'=>$giftids);
       }elseif($giftids){
           $gift_id = array();
       }else{
           $this->end(false,$this->app->_('请选择赠品!'));
       }
        
       if($giftObj->delete($gift_id)){
          $this->end(true, $this->app->_('删除成功'));
       }else{
          $this->end(false, $this->app->_('删除失败'));
       }
       
    }

    function setStatus($gid, $status) {
        if ($status == 'true') {
            $status = 1;
        } else {
            $status = 2;
        }

        kernel::database()->query("update  sdb_crm_gift set status='{$status}' where gift_id={$gid}");
        echo "<script>parent.MessageBox.success('设置已成功！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }

    function edit($gift_id=0)
    {
        if($_POST){
            $this->begin('index.php?app=crm&ctl=admin_gift&act=index');
            $data = $_POST;
            $data['gift_id'] = intval($data['id']);
            $data['update_time'] = time();
            //新增设置数量不可大于当前可售库存
            $salesMStockLib = kernel::single('material_sales_material_stock');
            $store = $salesMStockLib->getSalesMStockById($data['product_id']);

            if($data['giftset'] ==='0' && $data['gift_num']>$store){
                $this->end(false, '设置赠品数量不可以大于当前可用库存数!');
            }
            if($data['giftset'] =='1'){
                $data['gift_num'] = 0;
                $data['is_yujing'] = 'false';
            }
            if($data['is_yujing'] == 'true') {
                if(!$data['yj_num']) {
                    $this->end(false, '预警数量必填');
                }
                if(!$data['yj_mobile']) {
                    $this->end(false, '预警手机号必填');
                }
                $yjMobile = explode('#', $data['yj_mobile']);
                foreach ($yjMobile as $v) {
                    if(!preg_match("/^[0-9]{11}$/", $v)) {
                        $this->end(false, '预警手机号必须为11位数字');
                    }
                }
            }
            $this->app->model('gift')->save($data);
            $this->end(true, '保存成功');
        }

        if($gift_id>0){
            $rs = $this->app->model('gift')->dump($gift_id);
            $this->pagedata['rs'] = $rs;
            $this->display('admin/gift/edit.html');
        }else{
            echo('gift_id error.');
        }
    }

}