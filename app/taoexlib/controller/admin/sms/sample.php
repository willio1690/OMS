<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 短信模板类
 *
 * @package taoexlib
 * @author   zhangxuehui
 **/
class taoexlib_ctl_admin_sms_sample extends desktop_controller {
    /**
     * 列表所在组
     *
     * @var string
     **/
    var $workground = 'rolescfg';

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
        $this->sampleMdl = $this->app->model('sms_sample');
    }
    /**
     * 短信模板列表
     *
     * @param  void
     * @return html
     * @author
     **/

    public function index()
    {
        $params = array(
            'title'=>'短信模板',
            'use_buildin_recycle'=>false,
            'actions'=>array(
                array(
                    'label' => '添加模板',
                    'href' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=add_sample',
                    'target' => 'dialog::{width:900,height:600,title:\'添加模板\'}'
                ),
                array(
                    'label' => '删除',
                    'submit' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=del_sample',
                    'confirm' =>"确定删除选中模板？删除后不可恢复！"
                ),
                array(
                    'label' => '同步模板状态',
                    'href' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=sync_sample',
                    'target' => 'dialog::{width:600,height:400,title:\'同步模板状态\'}'

                ),
            ),
            'base_filter' => array('disabled'=>'false'),
        );
        $this->finder('taoexlib_mdl_sms_sample', $params);
    }
    /**
     * 显示菜单
     *
     * @param  void
     * @return html
     * @author
     **/
    public function _views()
    {
        $sub_menu = $this->_allVeiw();
        return $sub_menu;
    }
    /**
     * 显示所有未删除的模板
     *
     * @param  void
     * @return array
     * @author
     **/
    public function _allVeiw()
    {
        $sms_sample = $this->app->model('sms_sample');
        $base_filter = array('disabled'=>'false');
        $sub_menu = array(
                0 => array('label'=>app::get('taoexlib')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            );
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $sms_sample->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=taoexlib&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k++;
        }
        return $sub_menu;
    }
    /**
     * 显示添加模板页面
     *
     * @param  void
     * @return html
     * @author
     **/
    public function add_sample()
    {
        $this->pagedata['type_list']   = taoexlib_sms::getEventTypes();
        $this->pagedata['add_img']     = kernel::base_url(1).'/app/desktop/statics/bundle/btn_add.gif';
        $this->page("admin/sms/sample.html");
    }

    /**
     * 显示编辑模板页面
     *
     * @param  void
     * @return html
     * @author
     **/
    public function edit_sample()
    {
        $ids = $this->_request->get_get('p');
        $id = $ids[0];
        $sampleInfo = $this->sampleMdl->select()->columns()->where('id=?',$id)->instance()->fetch_row();
        if(count($sampleInfo)>0){
            $this->pagedata['type_list']   = taoexlib_sms::getEventTypes();
            $this->pagedata['add_img']     = kernel::base_url(1).'/app/desktop/statics/bundle/btn_add.gif';
            $this->pagedata['info']        = $sampleInfo;
            $this->pagedata['current_send_type'] = $sampleInfo['send_type'];
            $this->pagedata['action_flag'] = "edit";
        } else {
            $this->pagedata['nosample'] = 'true';
        }
        $this->page("admin/sms/sample.html");
    }

    /**
     * 保存模板信息(包括编辑和添加)
     *
     * @param  void
     * @return html
     * @author
     **/
    public function save_sample()
    {
        $this->begin("");
        $param =$this->_request->get_post();

        if(!$param['title']){
            $this->end(false,app::get('taoexlib')->_('请填写模板标题'));
        }

        if(!$param['content']){
            $this->end(false,app::get('taoexlib')->_('请填写模板内容'));
        }

        #判断内容是否有签名
         preg_match('/\{(短信签名)\}/',$param['content'],$filtcontent);
         
         preg_match('/\【(.*?)\】$/',$param['content'],$filtcontent1);

        if (!$filtcontent && !$filtcontent1) {
                $this->end(false,app::get('taoexlib')->_('请确认模板内容是否有短信签名'));
        }
        if ($filtcontent && $filtcontent1) {
            $this->end(false,app::get('taoexlib')->_('短信签名和【】一个模板只能包含其一!'));
        }
        
        $send_types = array_keys(taoexlib_sms::getEventTypes());
        if (!in_array($param['send_type'], $send_types) ){
            $this->end(false,app::get('taoexlib')->_('发送类型非法'));
        }

        if(!$param['sample_no']){
            $this->end(false,app::get('taoexlib')->_('请填写模板内容'));
        }

        $no = $this->sampleMdl->select()->columns()->where('sample_no=?',$param['sample_no'])->instance()->fetch_row();
        if(is_array($no) && count($no)>0&&($param['id']!=$no['id'])){
            $this->end(false,app::get('taoexlib')->_('模板编号不能重复'));
        }

         $param['status'] = $param['status']=='true'?1:0;
        if($param['id']&&($param['status'])==0){
             $res = $this->isStop($param['id']);
             if(count($res)>0){
                $this->end(false,app::get('taoexlib')->_('模板对应的绑定关系绑定为开启，无法暂停!'));
             }
        }
        
        $result = $this->sampleMdl->save_sample($param);
        if ($result) {
            if (!$filtcontent && $filtcontent1) {
                kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$filtcontent1[0]));
            }
            $this->end(true,app::get('taoexlib')->_('保存成功'));
        } else {
            $this->end(false,app::get('taoexlib')->_('保存失败，请重新添加'));
        }
    }

    /**
     * 逻辑删除模板
     *
     * @param  string
     * @return json
     * @author
     **/
    public function del_sample()
    {
        $this->begin("index.php?app=taoexlib&ctl=admin_sms_sample&act=index");
        $ids = $this->_request->get_post('id');
        $sampleInfo = $this->sampleMdl->getList('status,id',array('id|in'=>$ids));
        foreach ($sampleInfo as $key => $info) {
            if (!$this->_is_del($info['id'],$msg)) {
                $this->end(false,app::get('taoexlib')->_($msg));
            }else{
                 $this->sampleMdl->delete(array('id'=>$info['id']));
            }
        }
        $this->end(true,app::get('taoexlib')->_('删除成功'));
    }
    /**
     * 模板是否可以删除
     *
     * @param  $sample_id
     * @return bool
     * @author
     **/
    private function _is_del($sample_id,&$msg)
    {
        $bindInfos = app::get('taoexlib')->model('sms_bind')->getList('is_default,status', array('id'=>$sample_id));
        foreach ($bindInfos as $bindInfo) {
            if($bindInfo['is_default']=='1'){
                $msg = '此模板与默认绑定关系绑定，无法删除';
                return false;
            }
            if(count($bindInfo)>0){
                $msg = '请先删除绑定关系，再删除模板';
                return false;
            }
        }

        return true;
    }
    /**
     * 设置模板启用状态
     *
     * @param  void
     * @return void
     * @author
     **/
    public function setStatus($id,$status)
    {
        if($status =='1'){
            //暂停检查有无对应规则使用此模板
            if (!$this->isStop($id)) {
                $ruleList = '模板对应的绑定关系绑定为开启，无法暂停!';
                echo "<script>parent.MessageBox.error('$ruleList');</script>";
                exit;
            }else{
                $now_status = '0';
            }
        }else{
            $now_status = '1';
        }
        $data = array('id'=>$id,"status"=>$now_status);
        app::get('taoexlib')->model('sms_sample')->save($data);

        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
    /**
     * 检查是否可以暂停模板
     *
     * @param  void
     * @return array
     * @author
     **/
    public function isStop($sample_id)
    {
        $rs = $this->sampleMdl->getOpenBindBySampleId($sample_id);
        if (isset($rs['status'])&&($rs['status']=='1')) {
            return false;
        }
        return true;
    }
    
    public function getTmplConf(){
        $eventType = $_POST['event_type'];
        $conf = kernel::single('taoexlib_sms')->getTmplConfByEventType($eventType);
        echo json_encode($conf);exit;
    }

    /**
     * 更新短信模板审核状态
     *
     * @param  void
     * @return
     * @author
     **/
    public function sync_sample(){

        $finder_id = $_GET['finder_id'];
        $this->pagedata['finder_id'] = $finder_id;
        unset($finder_id);
        $this->page("admin/sync_sample.html");
    }

    public function do_sync_sample(){
        $result = kernel::single('taoexlib_request_sms')->sms_request('list','get',$param);
        echo json_encode($result);
    }
    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function send()
    {
        kernel::single('taoexlib_delivery_sms')->deliverySendMessage(4);
    }
   

    
    /**
     * 模板列表.
     * @param  
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function list_sample($id)
    {
        $params = array(
            'title'=>'商品列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'base_filter' => '',
            
        );
       
        $this->finder('taoexlib_mdl_sms_sample_items', $params);
    }

}