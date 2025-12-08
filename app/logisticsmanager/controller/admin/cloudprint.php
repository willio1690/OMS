<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_ctl_admin_cloudprint extends desktop_controller
{

    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $actions = array();
        $actions[] = array(
            'label' => '新建打印机',
            'href' => 'index.php?app=logisticsmanager&ctl=admin_cloudprint&act=add',
            'target' => "dialog::{width:800,height:600,title:'新建打印机'}",
        );
        $actions[] = array(
                    'label'   => app::get('ome')->_('删除打印机'),
                    'submit'  => "index.php?app=logisticsmanager&ctl=admin_cloudprint&act=toDelete",
                    'confirm' => '删除后无法恢复，您确定删除选择的打印机吗？',
                    'target'  => 'dialog::{width:600,height:250,title:\'删除打印机\'}',
        );
        $params = array(
            'title'=>'云打印机管理',
            'actions'               => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy'=> 'id DESC',
        );
        $this->finder('logisticsmanager_mdl_cloudprint', $params);


    }



    /**
     * 添加
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function add($id=0){

        $storeMdl = app::get('o2o')->model('store');
        $storeList = $storeMdl->getlist('store_bn,store_id,name',array());
        $this->pagedata['storeList'] = $storeList;

        $cloudprintMdl = app::get('logisticsmanager')->model('cloudprint');
        $cloudprint = $cloudprintMdl->dump(array('id'=>$id),'*');

        $this->pagedata['cloudprint'] = $cloudprint;

        $channelMdl = app::get('channel')->model('channel');

        $channel = $channelMdl->getlist('channel_id,channel_name',array('channel_type'=>'cloudprint'));

        $this->pagedata['channel'] = $channel;

        $this->display("admin/cloudprint/add.html");

    }

    /**
     * doSave
     * @return mixed 返回值
     */
    public function doSave(){

        $this->begin();

        if (empty($_POST['store_id'])){
            $this->end(false,'请选择门店');
        }
        $cloudprintMdl = app::get('logisticsmanager')->model('cloudprint');
        $channelMdl = app::get('channel')->model('channel');
        $id = $_POST['id'];
        if($id){
            $channel = $channelMdl->dump(array('channel_id'=>$_POST['channel_id']),'channel_type');
            $update_data = array(
                'channel_type'  => trim($channel['channel_type']),
                'disabled'      =>  $_POST['disabled'],
                'machine_code'  => trim($_POST['machine_code']),
                'store_id'      => $_POST['store_id'],
                'channel_id'      => $_POST['channel_id'],
            );
            $cloudprintMdl->update($update_data,array('id'=>$id));

        }else{
            $cloudprints = $cloudprintMdl->dump(array('code'=>$_POST['code']),'id');
            if ($cloudprints){
                $this->end(false,'云打印机编码重复，请重新填写');
            }
            $cloudprints = $cloudprintMdl->dump(array('store_id'=>$_POST['store_id']),'id');
            if ($cloudprints){
                $this->end(false,'门店已存在打印机');
            }
            $channel_id = $_POST['channel_id'];
            

            $channel = $channelMdl->dump(array('channel_id'=>$channel_id),'channel_type');

            $insert_data = array(
                'code'          => trim($_POST['code']),
                'channel_type'  => trim($channel['channel_type']),
                'disabled'      =>  $_POST['disabled'],
                'machine_code'  => trim($_POST['machine_code']),
                'store_id'      => $_POST['store_id'],
                'channel_id'      => $_POST['channel_id'],
            );
        
            $rs = $cloudprintMdl->insert($insert_data);

        }

        $this->end(true,'云打印机设置成功');
    }


    /**
     * bindNodeId
     * @param mixed $id ID
     * @param mixed $act_type act_type
     * @return mixed 返回值
     */
    public function bindNodeId($id,$act_type = 'bind')
    {
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $cloudprintMdl = app::get('logisticsmanager')->model('cloudprint');
        $cloudprints = $cloudprintMdl->dump(array('id'=>$id),'*');
        $node_id = $cloudprints['code'].$cloudprints['machine_code'].$cloudprints['msign'].$cloudprints['qr_key'].$cloudprints['store_id'];
        $node_id = sprintf('o%u', crc32(utils::array_md5($node_id)));
        if($act_type=='bind'){
            $update_data = array(
                'node_id'  => $node_id,
           
            );
            $cloudprintMdl->update($update_data,array('id'=>$id));
            
        }else{
            $update_data = array(
                'node_id'  => '',
           
            );
            $cloudprintMdl->update($update_data,array('id'=>$id));
            
        }
        
        $this->end(true);
    }

    /**
     * toDelete
     * @return mixed 返回值
     */
    public function toDelete()
    {
        $_POST = array_merge($_POST, array('disabled' => 'true'));

        

        $this->pagedata['request_url'] = $this->url.'&act=deletePrint';

        parent::dialog_batch('logisticsmanager_mdl_cloudprint',true, 500);
    }

    #删除
    /**
     * 删除Print
     * @return mixed 返回值
     */
    public function deletePrint()
    {

        parse_str($_POST['primary_id'], $postdata);

        if (!$postdata['f']) { echo 'Error: 请先选择订单';exit;}

        $retArr  = array(
            'itotal'    => 0,
            'isucc'     => 0,
            'ifail'     => 0,
            'err_msg'   => array(),
        );

        $cloudprintMdl = app::get('logisticsmanager')->model('cloudprint');
      

        $cloudprints = $cloudprintMdl->getList('id', $postdata['f'], $postdata['f']['offset'], $postdata['f']['limit']);
        
        $cloudprints = array_column($cloudprints, null, 'id');

        if (!$cloudprints) {echo 'Error: 未查询到打印机';exit;}

        $retArr['itotal'] = count($cloudprints);
        foreach($cloudprints as $v){
            kernel::database()->exec('DELETE FROM `sdb_logisticsmanager_cloudprint` WHERE id='.$v['id']);
        }
        $retArr['isucc']++;

        echo json_encode($retArr),'ok.';exit;
    }
    
}




?>
