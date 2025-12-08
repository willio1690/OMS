<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物料仓库处理[货位关系]Lib
 * 
 * @version 1.0
 */
class ome_branch_product_pos
{
    var $export_flag = false;
    
    /*
     * 更新货位库存
     */
    function change_store($branch_id, $product_id, $pos_id, $num, $operator='=')
    {
        $now = time();
        $store = "";
        switch($operator){
            case "+":
                $store = "store=IFNULL(store,0)+".$num;
                break;
            case "-":
                $store = " store=IF((CAST(store AS SIGNED)-$num)>0,store-$num,0) ";
                break;
            case "=":
            default:
                $store = "store=".$num;
                break;
        }
        $sql = 'UPDATE sdb_ome_branch_product_pos SET '.$store.' WHERE product_id='.$product_id.' AND branch_id='.$branch_id.' AND pos_id='.$pos_id;
        $rs = kernel::database()->exec($sql);
        if ($rs) return $this->count_store($product_id,$branch_id);
        return false;
    }

    /**
     * 默认货位库存不够减情况处理
     * @access public
     * @param int $pos_id 货位ID
     * @param int $branch_id 仓库 ID
     * @param int $product_id 货品ID
     * @param int $number 发货数量
     *
     */
    function update_default_pos($branch_id,$product_id,$pos_id,$number)
    {
        $oPos    = app::get('ome')->model('branch_product_pos');
        
        //获取默认货位库存值
        $default_pos = $oPos->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id,'pos_id'=>$pos_id), 'store');
        $remain_pos = $default_pos['store'] - $number;
        
        if ($remain_pos < 0)
        {
            $this->change_store($branch_id, $product_id, $pos_id, $number, '-');
            
            $remain_pos = abs($remain_pos);
            
            //--剩余的库存从其他货位随机消减
            $orderby = 'store DESC';
            $branch_product_pos_list = $oPos->getList('store,pos_id', array('branch_id'=>$branch_id,'product_id'=>$product_id,'default_pos'=>'false','store|than'=>'0'), 0, -1, $orderby);
            if ($branch_product_pos_list)
            {
                foreach ($branch_product_pos_list as $other_pos)
                {
                    $this->change_store($branch_id, $product_id, $other_pos['pos_id'], $remain_pos, '-');
                    if ($other_pos['store'] >= $remain_pos)
                    {
                        break;
                    }
                    else 
                    {
                        $remain_pos -= $other_pos['store'];
                    }
                }
            }
        }else{
            $this->change_store($branch_id, $product_id, $pos_id, $number, '-');
        }
    }

    /*
     * 统计所有此商品库存
     */
    function count_store($product_id,$branch_id=0)
    {
        $libBranchProduct    = kernel::single('ome_branch_product');
        
        $libBranchProduct->count_store($product_id, $branch_id);
        
        return true;
    }

    /*
     *
     * 根据仓库号和货号检查此是否已和某商品关联
     * ss备注：此方法已经没有地方调用
     */
    function get_branch_pos($product_id,$pos_id)
    {
        $branch_pos = kernel::database()->selectrow('SELECT count(*) as count FROM sdb_ome_branch_product AS p 
        left join sdb_ome_branch_product_pos AS s on p.product_id=s.product_id 
        WHERE p.product_id='.$product_id.' AND s.pos_id=\''.$pos_id.'\'');
        
        return $branch_pos['count'];
    }

    // ss备注：此方法已经没有地方调用
    function get_branch_pos_exist($product_id,$branch_id)
    {
        $branch_pos = kernel::database()->selectrow('SELECT count(*) as count FROM sdb_ome_branch_product AS s
        WHERE s.product_id='.$product_id.' AND s.branch_id='.$branch_id);

        return $branch_pos['count'];
    }

    function get_bps_nums($pid)
    {
        $sql    = 'SELECT count(*) as count FROM sdb_ome_branch_product_pos WHERE product_id='.$pid;
        $branch_pos = kernel::database()->selectrow($sql);
        
        return $branch_pos['count'];
    }

    function get_product_pos($product_id,$branch_id)
    {
        $sql = 'SELECT bp.store_position FROM
                (SELECT bpp.*
                        FROM (
                            SELECT pos_id,product_id
                            FROM sdb_ome_branch_product_pos WHERE branch_id = '.$branch_id.'
                            ORDER BY create_time DESC
                        )bpp
                    GROUP BY bpp.product_id
                 ) bb
                 LEFT JOIN sdb_ome_branch_pos bp
                    ON bp.pos_id = bb.pos_id
                    WHERE bb.product_id = '.$product_id;
        
        $branch_pos = kernel::database()->selectrow($sql);
        
        return $branch_pos['store_position'];
    }

    /*
     * 将某产品和仓库和货号关联
     *
     */
    function create_branch_pos($product_id,$branch_id,$pos_id)
    {
        $oBranch_product = app::get('ome')->model("branch_product");
        
        $product = $oBranch_product->dump(array('product_id'=>$product_id,'branch_id'=>$branch_id));
        
        $product_sdf = array(
            'branch_id'=>$branch_id,
            'product_id'=>$product_id
        );
        
        $pos_sdf = array(
            'operator' => kernel::single('desktop_user')->get_name(),
            'product_id'=>$product_id,
            'pos_id'=>$pos_id,
            'branch_id'=>$branch_id,
            'create_time'=>time()
        );
        
        $oPos   = app::get('ome')->model('branch_product_pos');
        $bpp    = $oPos->dump(array('product_id'=>$product_id,'pos_id'=>$pos_id), 'pp_id');
        if($bpp)
        {
            $pos_sdf['pp_id'] = $bpp['pp_id'];
        }
        
        if(empty($product))
        {
            $pos_sdf['default_pos']='true';
        }
        
        $product = $oBranch_product->save($product_sdf);
        
        if($product)
        {
            $oPos->save($pos_sdf);
        }
        
        $default = false;
        $pos = $this->get_pos($product_id,$branch_id);
        if ($pos)
        foreach ($pos as $v)
        {
            if ($v['default_pos'] == 'true')
            {
                $default = true;
            }
        }
        
        if(count($pos)==1 || (!empty($product) && $default==false))
        {
            $sql    = 'UPDATE sdb_ome_branch_product_pos SET default_pos=true WHERE pos_id='.$pos[0]['pos_id'];
            kernel::database()->exec($sql);
        }
        
        return $product;
    }

    function del_branch_product_pos($product_id, $pos_id)
    {
        $sql    = 'DELETE FROM sdb_ome_branch_product_pos WHERE product_id='.$product_id.' AND pos_id=\''.$pos_id.'\'';
        kernel::database()->exec($sql);
    }
    
    /*
     * 重置货位
     * ss备注：已经没用
     * \app\ome\controller\admin\stock.php        使用中
     * \app\console\controller\admin\stock.php    使用中
     */
    function reset_branch_pos($product_id,$branch_id,$pos_id)
    {
        $this->change_store($branch_id, $product_id, $pos_id, 0);
        
        kernel::database()->exec('DELETE FROM sdb_ome_branch_product_pos WHERE product_id='.$product_id.' AND pos_id=\''.$pos_id.'\'');
        
        $pos = $this->get_pos($product_id,$branch_id);
        if(count($pos)==0)
        {
            kernel::database()->exec('DELETE FROM sdb_ome_branch_product WHERE product_id='.$product_id.' AND branch_id='.$branch_id.'');

        }
        
        if(count($pos)>=1)
        {
            kernel::database()->exec('UPDATE sdb_ome_branch_product_pos SET default_pos=true WHERE pos_id='.$pos[0]['pos_id']);
        }
        
        $this->count_store($product_id,$branch_id);
    }
    
    /*
     * 获取某产品已经放置货位列表
     * @param int $product_id $branch_id
     * return array
     */
    function get_pos($product_id,$branch_id)
    {
        $sql    = "SELECT p.*,s.store_position FROM sdb_ome_branch_product_pos as p 
                   left join sdb_ome_branch_pos as s on p.pos_id=s.pos_id 
                   WHERE p.product_id='$product_id' AND p.branch_id='$branch_id' AND s.store_position!=''";
        $branch = kernel::database()->select($sql);
        
        return $branch;
    }
    
    /*
     *获取未和产品建立关联货位列表
     *$param int $product_id $branch_id
     * ss备注：ome中已经不使用此方法
     * \app\ome\controller\admin\stock.php        使用中
     * \app\console\controller\admin\stock.php    使用中
     */
    function get_unassign_pos($branch_id)
    {
        $sql = "SELECT bp.pos_id,bp.store_position FROM `sdb_ome_branch_pos` bp 
                LEFT JOIN `sdb_ome_branch_product_pos` bpp on bp.pos_id=bpp.pos_id 
                WHERE bpp.pos_id is NULL and bp.branch_id='$branch_id' ";
        $pos = kernel::database()->selectRow($sql);
        
        return array($pos);
    }
    
    /*
     * 获取未与仓库、货品建立关联的货位
     *  ss备注：此方法已经不使用，可以删除
     * \app\console\controller\admin\stock.php    使用中
     */
    function getPosByBranchProduct($branch_id=null, $product_id=null, $pos_name=null)
    {
        if ($pos_name)
        {
            $wheresql = "and bpos.store_position regexp '$pos_name'";
            $field = "bpos.pos_id,bpos.store_position";
        }else{
            $field = "bpos.pos_id";
        }
        
        $sql = " SELECT $field FROM `sdb_ome_branch_pos` bpos 
                 WHERE bpos.`branch_id`='$branch_id' $wheresql and bpos.`pos_id` not in (SELECT bps.pos_id FROM `sdb_ome_branch_product_pos` bps 
                 LEFT JOIN `sdb_ome_branch_product` bp on bps.product_id=bp.product_id and bp.branch_id='$branch_id' and bp.product_id='$product_id') ";
        $pos = kernel::database()->select($sql);
        
        return $pos;
    }
    
    /*
     * 根据名称获取货位
     * ss备注：此方法已经不使用，可以删除
     */
    function getPosByName($branch_id=null, $pos_name=null)
    {
        if ($pos_name)
        {
            $sql = " SELECT bpos.pos_id,bpos.store_position FROM `sdb_ome_branch_pos` bpos 
                     WHERE bpos.`branch_id`='$branch_id' ";
            $sql .= " AND bpos.store_position regexp '$pos_name'";
            $pos = kernel::database()->select($sql);
        }
        
        return $pos;
    }
    
    #获取货号在仓库的所有货位
    function getBranchPrducAllPos($branch_id = null,$product_id=null)
    {
        $sql = '
                select 
                   store_position 
                from  sdb_ome_branch_pos pos 
                left join sdb_ome_branch_product_pos branch 
                on branch.pos_id=pos.pos_id where branch.branch_id='.$branch_id.' and branch.product_id='.$product_id;
        $rows = kernel::database()->select($sql);
        
        if($rows)
        {
            return $rows;
        }
        
        return false;
    }
}
