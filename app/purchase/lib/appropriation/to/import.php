<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_appropriation_to_import {

    function run(&$cursor_id,$params)
    {
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        $oAppropriation = app::get('purchase')->model('appropriation');
		$oBranchProduct = app::get('ome')->model('branch_product');
		$oBranchProductPos = app::get('ome')->model('branch_product_pos');
		$appropriationSdf = $params['sdfdata'];

        foreach ($appropriationSdf['list'] as $data){
		    $product_id = $data['product_id'];
			$to_branch_id = $data['to_branch_id'];
			$to_pos_id = $data['to_pos_id'];
		    $to_branch_product_pos = $oBranchProductPos->dump(array('pos_id'=>$to_pos_id,'product_id'=>$product_id),'*');
			if( !$to_branch_product_pos )
			{
			    $libBranchProductPos->create_branch_pos($product_id,$to_branch_id,$to_pos_id);
			}
			
			$adata[] = $data;
         }
		 
		 $oAppropriation->to_savestore($adata,'',$appropriationSdf['op_name']);
		 
        return false;
    }
}
