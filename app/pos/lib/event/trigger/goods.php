<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_goods extends pos_event_trigger_common
{
    

    /**
     * 
     * 商品同步
     * @param 
     */
    public function add($id)
    {

        $syncproductMdl = app::get('pos')->model('syncproduct');
        $syncproducts = $syncproductMdl->dump(array('id'=>$id),'bm_id,id');
        
        $bm_id = $syncproducts['bm_id'];
        list($rs, $msg) = $this->syncProduct($bm_id);
        if(!$rs) {
            $updateData = array(
                'sync_status'   =>  '2',
                'sync_msg'      =>  $msg,
            );
           
        }else{
            $updateData = array(
                'sync_status'=>'1',
                'sync_msg'   =>'',
            );
            
        }

        $syncproductMdl->update($updateData,array('id'=>$syncproducts['id']));
        return $rs;
    }

    public function syncProduct($bm_id){
        $syncproductMdl = app::get('pos')->model('syncproduct');
        $syncproducts = $syncproductMdl->dump(array('bm_id'=>$bm_id),'bm_id,id');
        $bm_id = $syncproducts['bm_id'];
        $materialMdl    = app::get('material')->model('basic_material');
        $codeMdl        = app::get('material')->model('codebase');
        $brandMdl = app::get('ome')->model('brand');
        $typeMdl = app::get('ome')->model('goods_type');
        $extMdl = app::get('material')->model('basic_material_ext');
        $materials = $materialMdl->dump($bm_id,'material_bn,material_name,material_spu,cat_id,type,tax_rate,tax_code,tax_name,serial_number,source,visibled,type');
        //需要判断商品类型
        if($materials['type'] == '4'){//
            $combination_items = app::get('material')->model('basic_material_combination_items')->getList('*', ['pbm_id'=>$bm_id]);
            if(empty($combination_items)){
                return [false,'礼盒商品未设置商品明细信息'];
            }
            $bm_ids = array_column($combination_items,'bm_id');
            $combexts = $extMdl->getlist('unit,bm_id',array('bm_id'=>$bm_ids));

            $combexts = array_column($combexts,null,'bm_id');

            foreach($combination_items as $k=>$v){
                $comunit = $combexts[$v['bm_id']] ? $combexts[$v['bm_id']]['unit'] : '';
                $combination_items[$k]['unit'] = $comunit;
            }
        }

        //商品分类
        $cat_id = $materials['cat_id'];
        $codes = $codeMdl->dump(array('bm_id'=>$bm_id),'code');
        $exts = $extMdl->dump(array('bm_id'=>$bm_id),'brand_id,cat_id,retail_price,unit');

        $types = $typeMdl->dump(array('type_id'=>$exts['cat_id']),'name');
        $brands = $brandMdl->dump(array('brand_id'=>$exts['brand_id']),'brand_code,brand_name');

        $catMdl        = app::get('material')->model('basic_material_cat');
        $cats = $catMdl->dump(array('cat_id'=>$cat_id),'cat_name,parent_id');
        $parent_id = $cats['parent_id'];

        if($parent_id>0){

            $parent_cats = $catMdl->dump(array('cat_id'=>$parent_id),'cat_name');
            $parent_cat_name = $parent_cats['cat_name'];
        }
      
        

      
        
        $params = array(
            'material_bn'   =>  $materials['material_bn'],
            'material_name' =>  $materials['material_name'],
            'material_spu'  =>  $materials['material_spu'],
            'material_type' =>  $materials['type'],
            'barcode'       =>  $codes['code'],
            'brand_code'    =>  $brands['brand_code'],
            'brand_name'    =>  $brands['brand_name'],
            'type_name'     =>  $types['name'],
            'parent_cat_name'=> $parent_cat_name,
            'cat_name'      =>  $cats['cat_name'],
            'retail_price'  =>  $exts['retail_price'],
            'tax_rate'      =>  $materials['tax_rate'],
            'tax_code'      =>  $materials['tax_code'],
            'tax_name'      =>  $materials['tax_name'],
       
            'serial_number' =>  $materials['serial_number'],
            'source'        =>  $materials['source'],
            'visibled'      =>  $materials['visibled'],
            'unit'          =>  $exts['unit'],
        );
        if($combination_items){



            $params['combination_items'] = $combination_items;
        }
        if(empty($params['brand_code'])){
            return [false,'品牌不可以为空'];
        }
        if(empty($params['type_name'])){
            return [false,'商品类型不可以为空'];
        }
        if(empty($params['cat_name']) || empty($parent_cat_name)){
            return [false,'分类不可以为空'];
        }
        $channel_type = 'store';
        $channels = $this->getChannelId('pekon');
        $channel_id = $channels['store_id'];

        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->goods_add($params);
        if($result['rsp'] == 'succ'){
            
            $rs = [true,'成功'];
        }else{
           
            $rs = [false,$result['msg'] ? $result['msg'] : '失败'];
        }

        
        return $rs;
    }
    
    /**
     * syncprice
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function syncprice($data){
        $syncpriceMdl = app::get('pos')->model('productprice');
       
        $ids = array_column($data, 'id');
        $syncpriceMdl->update(array('sync_status'=>'3'),array('id'=>$ids));

        $params = [];

        $material_bns  = array_column($data,'material_bn');

        
        foreach($data as $v){
            

            $params[$v['disabled']][] = array(
                'material_bn'   =>  $v['material_bn'],
                'material_name' =>  $v['material_name'],
               
                'store_bn'      =>  $v['store_bn'],
                'bm_id'         =>  $v['bm_id'],
                'store_id'      =>  $v['store_id'],
                'id'            =>  $v['id'],
            );
        }
        

        $channel_type = 'store';

        $channels = $this->getChannelId('pekon');
        $channel_id = $channels['store_id'];
   
        foreach($params as $k=>$item){
            $action = 'update';
            if($k == 'true'){
                $action = 'delete';
            }
          
            $sdf = ['action'=>$action,'items'=>$item];
          
            $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->goods_syncprice($sdf);
       
            if($result['rsp'] == 'succ'){
                $updateData = array(
                    'sync_status'=>'1',
                    'msg_id'    =>$result['msg_id'],
                );
              
            }else{
                $updateData = array(
                    'sync_status'=>'2',
                    'msg_id'    =>$result['msg_id'],
                );
            
            }
         
            foreach($item as $dv){
                $syncpriceMdl->update($updateData,array('id'=>$dv['id']));
            }

        }
        
        
        
        return[true,'成功'];
    }

    /**
     * syncMaterial
     * @param mixed $bm_id ID
     * @return mixed 返回值
     */
    public function syncMaterial($bm_id){
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $db      = kernel::database();
        $data    = array();
        $syncproductMdl = app::get('pos')->model('syncproduct');
        $data = kernel::single('pos_productsync')->get_wms_goods($bm_id);
        if($data){//`material_bn`,`bm_id`,`type`
            foreach($data as $v){
                $syncproducts = $syncproductMdl->db_dump(array('material_bn'=>$v['bn']),'id');
                if(!$syncproducts){
                    $tmpdata = array(
                        'material_bn'   =>  $v['bn'],
                        'bm_id'         =>  $v['product_id'],
                        'type'          =>  $v['type'],
                    );
                  
                    $rs = $syncproductMdl->save($tmpdata);

                    if($rs){
                        $this->add($tmpdata['id']);
                    }

                }
                
            }
            
        }
        
        return $data;
    }

    
}
