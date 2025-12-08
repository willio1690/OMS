<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016-04-05
 * @describe 当当平台处理店铺商品相关类
 */
class erpapi_shop_matrix_dangdang_request_product extends erpapi_shop_request_product {

    protected function getUpdateStockApi() {
        base_kvstore::instance('erpapi/request/abstract')->fetch('dangdang_shop_categoryList',$dangdang_shop_categoryList);
        if(empty($dangdang_shop_categoryList[$this->__channelObj->channel['shop_id']])){
            #所在当当店铺在ERP没有分类，则，去获取一次接口分类，以检测店铺是图书还是非图书
            $this->getDangdangCategoryList();
            #打接口后，再判断一次，店铺分类是 什么分类
            base_kvstore::instance('erpapi/request/abstract')->fetch('dangdang_shop_categoryList',$dangdang_shop_categoryList);
            #图书分类
            if($dangdang_shop_categoryList[$this->__channelObj->channel['shop_id']] == 1){
                return SHOP_UPDATE_DANGDANG_QUANTITY_LIST_RPC;
            }
        }elseif($dangdang_shop_categoryList[$this->__channelObj->channel['shop_id']] == 1){
            return SHOP_UPDATE_DANGDANG_QUANTITY_LIST_RPC;
        }
        return SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC;
    }

    #获取当当店铺类目
    /**
     * 获取DangdangCategoryList
     * @return mixed 返回结果
     */

    public function getDangdangCategoryList(){
        $title = '获取当当店铺类目';
        $ret = $this->__caller->call(SHOP_GET_DANGDANG_SHOP_CATEGORYLIST, array(), array(),$title, 5);
        if($ret['rsp'] == 'succ') {
            #本次之前，所有当当店铺的类目
            base_kvstore::instance('erpapi/request/abstract')->fetch('dangdang_shop_categoryList',$dangdang_shop_categoryList);
            $_categoryList = json_decode($ret['data'],true);
            $categoryList = json_decode($_categoryList['msg'],true);
            if(empty( $categoryList)){
                return true;
            }
            $_all_dangdang_categoryList =  $categoryList['shopCategories']['categoryList']['category'];
            #只有一个类型
            if(empty($_all_dangdang_categoryList[0])){
                $all_dangdang_categoryList[0] = $_all_dangdang_categoryList;
            }else{
                $all_dangdang_categoryList = $_all_dangdang_categoryList;
            }
            if(!empty($all_dangdang_categoryList)){
                $dangdang_book = 1;#默认是图书
                foreach($all_dangdang_categoryList as $val){
                    #当当店铺不能跨类型，所以只要检测到一个大于400000且不等于4003973的，就属于非图书类型店铺
                    if(($val['catInuse'] > 4000000) && ($val['catInuse'] != 4003973)){
                        $dangdang_book = 2; #非图书类目
                        break;
                    }
                }
            }
            $dangdang_shop_categoryList[$this->__channelObj->channel['shop_id']] = $dangdang_book;#每个店铺的类目
            base_kvstore::instance('erpapi/request/abstract')->store('dangdang_shop_categoryList',$dangdang_shop_categoryList);
        }
        return $ret;
    }
}