<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_api_stock extends desktop_controller{
    var $workground = "api_stock_log";
	
    function index($status='all'){
        $base_filter = '';
		$orderby = ' log_id ';
		
        switch($status){
            case 'sending':
                $this->title = '等待同步';
                $base_filter = array('status'=>'sending');
                break;
            case 'running':
                $this->title = '同步中';
                $base_filter = array('status'=>'running');
                break;
            case 'success':
                $this->title = '同步成功';
                $base_filter = array('status'=>'success');
                break;
            case 'fail':
                $this->title = '同步失败';
                $base_filter = array('status'=>'fail');
                break;
			default:
                $this->title = '库存同步管理';
                break;
        }
        $actions[] = array(
            'label' => app::get('ome')->_('批量删除'),
            'submit' => 'index.php?app=ome&ctl=admin_api_stock&act=batch_delete&finder_id='.$_GET['finder_id'],
            'target' => "dialog::{width:500,height:200,title:'批量删除'}",
        );
        
        if($status=='sending' || $status=='fail') {
            $actions[] = array(
                   'label' => '批量同步',
                   'submit' => 'index.php?app=ome&ctl=admin_api_stock&act=batch_retry_stock&status='.$status.'&finder_id='.$_GET['finder_id'],
                   'target' => "dialog::{width:350,height:100,title:'批量同步'}",
                 );
        }
        $params = array(
            'title'=>$this->title,
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' => $orderby,
        );
        
        if($base_filter){
            $params['base_filter'] = $base_filter; 
        }
        
        $this->finder('ome_mdl_api_stock_log',$params);
    }
    
    function _views(){
		$oApiStock = $this->app->model('api_stock_log');
        $base_filter = array('disabled'=>'false');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('库存同步管理'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('等待同步'),'filter'=>array('status' => 'sending'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('同步中'),'filter'=>array('status' => 'running'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('同步成功'),'filter'=>array('status' => 'success'),'optional'=>false),
            4 => array('label'=>app::get('base')->_('同步失败'),'filter'=>array('status' => 'fail'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oApiStock->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&p[0]='.$v['filter']['status'].'&view='.$i++;
        }
        return $sub_menu;
	}
    
    /**
     * 批量同步库存到前端店铺
     * 更新指定商品的max_store_lastmodify
     */
    function batch_retry_stock()
    {
        $basicMaterialStockObj = app::get('material')->model('basic_material_stock');#基础物料_库存
        
        $log_ids = $_POST['log_id'];
        $isSelectedAll = $_POST['isSelectedAll'];
        $app = $_POST['app'];
        $ctl = $_POST['ctl'];
        $act = $_POST['act'];
        $view = $_POST['view'];
        $_finder = $_POST['_finder'];
        $status = $_GET['status'];
        
        $update['status'] = 'sending';
        $update['msg'] = '';
        //$update['memo'] = '';
        $update['createtime'] = time();
        $update['op_user'] = kernel::single('desktop_user')->get_name();
        $update['op_time'] = time();
        $update['op_userip'] = $this->GetIP();
        
        if($isSelectedAll == '_ALL_') {//全选
            unset($log_ids);
            if($status) {
                $filter = array('status'=>$status);
            }
            $rs = $this->app->model('api_stock_log')->getList('log_id,product_id',$filter);
            foreach((array)$rs as $v){
                $product_ids[] = $v['product_id'];
                $log_ids[] = $v['log_id'];
            }
            $this->app->model('api_stock_log')->update($update,$filter);
        }else{//指定的id
            $rs = $this->app->model('api_stock_log')->getList('product_id',array('log_id'=>$log_ids));
            foreach((array)$rs as $v){
                $product_ids[] = $v['product_id'];
            }
            $this->app->model('api_stock_log')->update($update,array('log_id'=>$log_ids));
        }

        if($product_ids) {
            echo('正在同步库存数据，请稍等……');
            $basicMaterialStockObj->update(array('max_store_lastmodify_upset_sql'=>'UNIX_TIMESTAMP()'),array('bm_id'=>$product_ids));
        }else{
            echo('没有需要同步的数据');
        }
        
        echo "<script>window.setTimeout(\"$$('.dialog').getLast().retrieve('instance').close();\",3000)</script>";
        die();
    }
    
    function GetIP(){
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
           $cip = $_SERVER["HTTP_CLIENT_IP"];
        else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
           $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if(!empty($_SERVER["REMOTE_ADDR"]))
           $cip = $_SERVER["REMOTE_ADDR"];
        else
           $cip = "未知ip";
        return $cip;
    }
    
    /**
     * 批量删除
     * @date 2025-04-14 下午2:04
     */
    public function batch_delete()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
    
        $this->pagedata['request_url'] = $this->url .'&act=ajaxBatchDelete';
    
        //调用desktop公用进度条(第4个参数是增量传offset,否则默认一直为0)
        parent::dialog_batch('ome_mdl_api_stock_log', false, 500);
    }
    
    public function ajaxBatchDelete()
    {
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'err_msg' => [],
        );
    
        //获取发货单号
        parse_str($_POST['primary_id'], $postdata);
        if(!$postdata){
            echo 'Error: 请先选择数据';
            exit;
        }
    
        //filter
        $filter = $postdata['f'];
        $offset = intval($postdata['f']['offset']);
        $limit = intval($postdata['f']['limit']);
    
        if(empty($filter)){
            echo 'Error: 没有找到查询条件';
            exit;
        }
    
        $stockLogMdl = $this->app->model('api_stock_log');
    
        //data
        $dataList = $stockLogMdl->getList('log_id', $filter, $offset, $limit);
        //check
        if(empty($dataList)){
            echo 'Error: 没有获取到库存同步日志数据';
            exit;
        }
        
        $log_ids = array_column($dataList,'log_id');

        //count
        $count = count($dataList);
        $retArr['itotal'] = $count;
    
        $stockLogMdl->delete(['log_id'=>$log_ids]);
    
        $retArr['isucc'] = $count;
        
    
        echo json_encode($retArr),'ok.';
        exit;
    }
}
?>