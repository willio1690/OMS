<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 条码模型层
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class material_mdl_barcode extends material_mdl_codebase{

    /**
     * 定义继承模型层的引用表
     * 
     * @param Null
     * @return String
     */

    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_material_codebase';
        }else{
           $table_name = 'codebase';
        }
        return $table_name;
    }

    /**
     * 过滤条件格式化
     * 
     * @param Array $filter
     * @param String $tableAlias
     * @param Array $baseWhere
     * @return Array
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $type= material_codebase::getBarcodeType();
        $where = ' type='.$type.' ';
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }

    /**
     * 导入模板的标题
     * 
     * @param Null
     * @return Array
     */
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     /**
      * 导入导出的标题
      * 
      * @param Null
      * @return Array
      */
     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'barcode':
                $this->oSchema['csv'][$filter] = array(
                    '*:基础物料编码' => 'material_bn',
                    '*:条码' => 'code',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

    /**
     * 准备导入的参数定义
     * 
     * @param Null
     * @return Null
     */
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    /**
     * 准备导入的数据主体内容部分检查和处理
     * 
     * @param Array $data
     * @param Boolean $mark
     * @param String $tmpl
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    /**
     * 准备导入的数据明细内容部分检查和处理
     * 
     * @param Array $row
     * @param String $title
     * @param String $tmpl
     * @param Boolean $mark
     * @param Boolean $newObjFlag
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        
        if (empty($row)){
            return true;
        }
        $mark = false;

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            
            return $titleRs;
        }else{
            $re = base_kvstore::instance('material_barcode')->fetch('barcode-'.$this->ioObj->cacheTime,$fileData);
        
            if( !$re ) $fileData = array();
        
            if(!$row[0]){
                $msg['error'] = "基础物料编码必须填写";
                return false;
            }

            if(!$row[1]){
                $msg['error'] = "条码必须填写";
                return false;
            }

            if(isset($this->barcode_nums)){
                $this->barcode_nums ++;
                if($this->barcode_nums > 5000){
                    $msg['error'] = "导入的数量量过大，请减少到5000个以下！";
                    return false;
                }
            }else{
                $this->barcode_nums = 0;
            }

            //判断基础物料是否存在
            $basicMaterialObj = app::get('material')->model('basic_material');
            $basicMaterialInfo = $basicMaterialObj->getList('material_bn,bm_id',array('material_bn'=>$row[0]));
            if(!$basicMaterialInfo){
                $msg['error'] = "基础物料不存在：".$row[0];
            }

            //判断基础物料是否有条码设置
            if($basicMaterialInfo){
                $barcodeInfo = $this->getList('code',array('bm_id'=>$basicMaterialInfo[0]['bm_id']));
                if($barcodeInfo){
                    $msg['error'] = "该物料已设置过条码：".$row[0];
                }

                //判断条码是否已被占用
                $codeInfo = $this->getList('bm_id',array('code'=>$row[1]));
                if ($codeInfo && ($codeInfo[0]['bm_id'] != $basicMaterialInfo[0]['bm_id'])){
                    $msg['error'] = "该条码已被占用：".$row[1];
                }

                //重置基础物料字段为bm_id
                $row[0] = $basicMaterialInfo[0]['bm_id'];
            }

            $fileData['barcode']['contents'][] = $row;
            base_kvstore::instance('material_barcode')->store('barcode-'.$this->ioObj->cacheTime,$fileData);

        }
        return null;
    }

    /**
     * 完成基础物料的关联条码导入
     * 
     * @param Null
     * @return Null
     */
    function finish_import_csv(){
        base_kvstore::instance('material_barcode')->fetch('barcode-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('material_barcode')->store('barcode-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('barcode') );
        $pSchema = $this->oSchema['csv']['barcode'];
        $oQueue = app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
        
        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();
        
        foreach ($aP['barcode']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }
        
        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'基础物料条码导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'material',
                    'mdl' => 'barcode'
                ),
                'worker'=>'material_barcode_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }
}
