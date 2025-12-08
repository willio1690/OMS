<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_mdl_shop_skus extends dbeav_model{

    var $defaultOrder = array('download_time',' DESC');

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
        switch( $filter )
        {
            case 'item':
                $this->oSchema['csv'][$filter] = array(
                                                    '*:货品名称' => 'shop_title',
                                                    '*:货品编码' => 'shop_product_bn',
                                                    '*:后端商品名称' => 'product_name',
                                                    '*:后端商品编码' => 'product_bn',
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
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        $shopProductObj      = app::get('tbo2o')->model('shop_products');
        $skuObj              = app::get('tbo2o')->model('shop_skus');
        
        if (empty($row))
        {
            return true;
        }
        $mark = false;

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            
            # [防止重复]记录组织编码
            $this->sku_bn_list      = array();
            $this->product_bn_list       = array();
            $this->salesm_nums           = 1;
            
            return $titleRs;
        }else{
            $re = base_kvstore::instance('tbo2o_shop_skus')->fetch('skus-'.$this->ioObj->cacheTime, $fileData);

            if( !$re ) $fileData = array();

            if(!$row[0]){
                $msg['error'] = "货品名称必须填写,货品编码：". $row[1];
                return false;
            }

            if(!$row[1]){
                $msg['error'] = "货品编码必须填写,货品名称：". $row[0];
                return false;
            }

            if(!$row[2]){
                $msg['error'] = "后端商品名称必须填写,货品编码：". $row[1];
                return false;
            }

            if(!$row[3]){
                $msg['error'] = "后端商品编码必须填写,货品编码：". $row[1];
                return false;
            }

            if(isset($this->salesm_nums)){
                $this->salesm_nums ++;
                if($this->salesm_nums > 5000){
                    $msg['error'] = "导入的数量量过大，请减少到5000个以下！";
                    return false;
                }
            }
            
            //[防止重复]检查宝贝编码及物料编号
            if(in_array($row[1], $this->sku_bn_list))
            {
                $msg['error'] = 'Line '.$this->salesm_nums.'：货品编码【'. $row[1] .'】重复！';
                return false;
            }
            $this->sku_bn_list[]    = $row[1];
            
            if(in_array($row[3], $this->product_bn_list))
            {
                $msg['error'] = 'Line '.$this->salesm_nums.'：后端商品编码【'. $row[3] .'】重复！';
                return false;
            }
            $this->product_bn_list[]    = $row[3];
            
            //检查宝贝编码是否存在
            $skuRow    = $skuObj->dump(array('shop_product_bn'=>$row[1]), 'id');
            if(empty($skuRow))
            {
                $msg['error'] = "货品不存在,货品编码：". $row[1];
                return false;
            }
            
            //检查后端商品编码是否存在
            $basicRow    = $shopProductObj->dump(array('bn'=>$row[3]), 'id, name');
            if(empty($basicRow))
            {
                $msg['error'] = "后端商品不存在,货品编码：". $row[3];
                return false;
            }
            
            //检查货品编码是否已经关联其它宝贝
            $skuChk    = $skuObj->dump(array('product_id'=>$basicRow['id']), 'id, shop_product_bn');
            if($skuChk)
            {
                $msg['error'] = "后端商品编码:". $row[3] .",已关联货品:". $skuRow['shop_product_bn'];
                return false;
            }
            
            #拼接数据
            $sdf    = array(
                        'id' => $skuRow['id'],
                        'shop_product_bn' => $row[1],
                        'product_id' => $basicRow['id'],
                        'product_bn' => $row[3],
                        'product_name' => $basicRow['name'],
            );
            
            $fileData['salesm']['contents'][] = $sdf;
            base_kvstore::instance('tbo2o_shop_skus')->store('skus-'.$this->ioObj->cacheTime,$fileData);
        }
        
        return null;
    }

    /**
     * 完成关联数据的导入
     *
     * @param Null
     * @return Null
     */
    function finish_import_csv(){
        base_kvstore::instance('tbo2o_shop_skus')->fetch('skus-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('tbo2o_shop_skus')->store('skus-'.$this->ioObj->cacheTime,'');

        $oQueue = app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['salesm']['contents'] as $k => $aPi){
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
                'queue_title'=>'关联后端商品导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=> $v,
                    'app' => 'tbo2o',
                    'mdl' => 'shop_skus'
                ),
                'worker'=>'tbo2o_shop_skus_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        
        //记录日志
        $operationLogObj    = app::get('ome')->model('operation_log');
        $operationLogObj->write_log('shop_skus_import@wms', 0, "批量导入关联后端商品,本次共导入". count($aP['salesm']['contents']) ."条记录!");
        
        return null;
    }
}
