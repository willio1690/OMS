<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 抽象类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.01.16
 */
abstract class ediws_abstract
{
    //本地存储文件目录
    public $_local_path = DATA_DIR;
    
    //每页处理行数
    public $_page_size = 100;
    
    public $local_path = '/ttpos/';
    
    /**
     * 成功输出
     * 
     * @param mixed $msg msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function succ($msg='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'data'=>$data);
    }
    
    /**
     * 失败输出
     * 
     * @param string $msg
     * @param string $data
     * @return array
     */
    /**
     * error
     * @param mixed $error_msg error_msg
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function error($error_msg, $data=null)
    {
        return array('rsp'=>'fail', 'msg'=>$error_msg, 'error_msg'=>$error_msg, 'data'=>$data);
    }
    
    /**
     * 去除内容中的特定字符
     * 
     * @param string $str
     * @return mixed
     */

    public function charFilter($str)
    {
        return str_replace(array("\t","\r","\n",'"',"\\", '“', '"', "'"), array(''), $str);
    }
    
    /**
     * 获取京东云仓店铺信息
     * 
     * @return void
     */
    public function getJdCloudShop(&$error_msg=null)
    {
        $storeObj = app::get('vfapi')->model('storemapping');
        
        //check
        if(!defined('EDI_SHOP_BN')){
            $error_msg = '请先在config中配置京东云仓店铺编码';
            return array();
        }
        
        //获取京东云仓店铺信息
        $shopInfo = $storeObj->dump(array('erp_store'=>EDI_SHOP_BN), '*');
        if(empty($shopInfo)){
            $error_msg = '京东云仓店铺信息不存在';
            return array();
        }
        
        //shop_bn
        $shop_bn = $shopInfo['erp_store'];
        
        return array($shop_bn=>$shopInfo);
    }
    
   
    
    /**
     * 获取渠道类型列表
     * 
     * @return void
     */
    public function getChannelTypes(&$error_msg=null)
    {
        $channelTypes = array(
            array('type'=>'jd_account', 'name'=>'京东入仓'),
            array('type'=>'jd_cloud', 'name'=>'京东云仓'),
        );
        
        return $channelTypes;
    }
    
    /**
     * 获取冲红类型列表
     * 
     * @return void
     */
    public function getBusinessTypes(&$error_msg=null)
    {
        $businessTypes = array(
            array('type'=>'duichong', 'name'=>'对冲单据'),
            array('type'=>'jiesuan', 'name'=>'结算单据'),
        );
        
        return $businessTypes;
    }
    
    /**
     * 获取ARDC对冲单据号
     * @todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
     * 
     * @param $original_bn
     * @return void
     */
    public function getArdcBillBn($original_bn)
    {
        $big_year = date('Y', time());
        $small_year = date('y', time());
        
        //去除ARDC、ARJS字母
        $original_bn = str_replace(array('ARDC','ARJS'), '', $original_bn);
        
        //替换年份为小写(减少2个字符)
        $original_bn = str_replace(array('S'.$big_year, 'A'.$big_year), $small_year, $original_bn);
        
        //去除S、A字母
        $original_bn = str_replace(array('S','A'), '', $original_bn);
        
        //对冲单据号
        $bill_bn = 'ARDC'. $original_bn;
        
        return $bill_bn;
    }
    
    /**
     * 获取ARJS结算单据号
     * @todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
     * 
     * @param $original_bn
     * @return void
     */
    public function getArjsBillBn($original_bn)
    {
        $big_year = date('Y', time());
        $small_year = date('y', time());
        
        //去除ARDC、ARJS字母
        $original_bn = str_replace(array('ARDC','ARJS'), '', $original_bn);
        
        //替换年份为小写(减少2个字符)
        $original_bn = str_replace(array('S'.$big_year, 'A'.$big_year), $small_year, $original_bn);
        
        //去除S、A字母
        $original_bn = str_replace(array('S','A'), '', $original_bn);
        
        //结算单据号
        $bill_bn = 'ARJS'. $original_bn;
        
        return $bill_bn;
    }
    
    
    
   
    
}