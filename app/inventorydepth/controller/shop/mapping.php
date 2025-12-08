<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料控制层
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class inventorydepth_ctl_shop_mapping extends desktop_controller
{
    public function index()
    {
        kernel::single('inventorydepth_shop_mapping')->set_params($_REQUEST)->display();
    }
    
    //前段店铺拉取商品
    public function downloadAllGoods()
    {
        $shop                        = app::get('ome')->model('shop');
        $shop_list                   = $shop->getList('shop_id,name', ['shop_type' => 'luban']);
        $this->pagedata['shop_list'] = $shop_list;
        $this->pagedata['goods_type'] = array(
            ['key'=>'','value'=>'全部'],
            ['key'=>'0','value'=>'上架'],
            ['key'=>'1','value'=>'下架'],
        );
        
        //开始时间(默认为一个月前)
        $start_time = date('Y-m-d', strtotime('-1 month'));
        $this->pagedata['start_time'] = $start_time;
        
        $this->display('shop/mapping/download_all_goods.html');
    }
    
    public function ajaxDownloadAllGoods()
    {
        parse_str($_POST['shopId'], $postdata);
        
        $retArr = array(
            'itotal'  => 0,
            'isucc'   => 0,
            'ifail'   => 0,
            'total'   => 0,
            'err_msg' => array(),
        );
        if (!$postdata) {
            $retArr['err_msg'] = ['请先选择店铺'];
            echo json_encode($retArr);
            exit;
        }
        
        //page
        $page = isset($_POST['nextPage']) && $_POST['nextPage'] > 1 ? $_POST['nextPage'] : 1;
        
        //params
        $params = array(
                'shop_id' => $_POST['shopId'],
                'goods_type' => $goodsType,
        );
        
        //商品状态(0上架 1下架)
        if($_POST['goodsType']==='0' || $_POST['goodsType']==='1'){
            $params['goods_type'] = intval($_POST['goodsType']);
        }
        
        //开始时间(年-月-日)
        if($_POST['startTime']){
            $params['start_time'] = $_POST['startTime'];
        }
        
        //request
        $rs = kernel::single('inventorydepth_shop_mapping')->downloadAllGoods($params, $page);
        
        if ($rs['rsp'] == 'succ') {
            $retArr['itotal']    += ($rs['succ'] - $rs['fail']);
            $retArr['ifail']     += $rs['ifail'];
            $retArr['total']     = $rs['all'];
            $retArr['next_page'] = $rs['next_page'];
        } else {
            $retArr['err_msg'] = [$rs['err_msg']];
        }
        
        echo json_encode($retArr);
        exit;
    }
}
