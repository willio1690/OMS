<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_event_receive_goods
{

    /**
     * 店铺商品同步 
     *
     * @return void
     * @author 
     **/
    public function add($data)
    {
        $itemMdl = app::get('inventorydepth')->model('shop_items');

        $rs = $itemMdl->saveItem($data['item'],$data['shop'], false);

        if ($rs == true) {
            //是否安装了应用
            if(in_array($data['shop']['shop_type'], array('taobao', 'tmall')) && app::get('dchain')->is_installed()) {
                //[翱象系统]店铺是否签约
                $aoxiangLib = kernel::single('dchain_aoxiang');
                $isAoxiang = $aoxiangLib->isSignedShop($data['shop']['shop_id'], $data['shop']['shop_type']);
                
                //get config
                $aoxiangConfig = $aoxiangLib->getAoxiangSyncConfig($data['shop']['shop_id']);
                
                //创建商品
                if($isAoxiang && $aoxiangConfig['sync_product'] != 'false'){
                    $axInventoryLib = kernel::single('dchain_inventorydepth');
                    $result = $axInventoryLib->savePlatformSkus($data['item'], $data['shop']);
                }
            }
            
            return array('rsp'=>'succ','msg' => '同步成功');
        } else {
            return array('rsp'=>'fail','msg' => '同步失败');
        }
    }

    /**
     * 删除商品
     *
     * @return void
     * @author 
     **/
    public function delete($data)
    {
        if (!$data['item'] || !$data['shop']) return array('rsp'=>'fail','msg'=>'缺少item或shop');

        $itemMdl = app::get('inventorydepth')->model('shop_items');
        $skuMdl  = app::get('inventorydepth')->model('shop_skus');

        $itemMdl->delete(array('iid'=>$data['item']['iid'],'shop_id'=>$data['shop']['shop_id']));
        $skuMdl->delete(array('shop_iid'=>$data['item']['iid'],'shop_id'=>$data['shop']['shop_id']));

        return array('rsp'=>'succ','msg' => '删除成功');
    }
}