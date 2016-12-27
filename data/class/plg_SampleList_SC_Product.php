<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2014 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once CLASS_REALDIR . 'SC_Product.php';

class plg_SampleList_SC_Product extends SC_Product
{
    /**
     * 商品規格IDから商品規格を取得する.
     *
     * 削除された商品規格は取得しない.
     *
     * @param  integer $productClassId 商品規格ID
     * @return array   商品規格の配列
     */
    public function getProductsClass($productClassId)
    {
        $arrProduct = parent::getProductsClass($productClassId);

        // サンプル商品の場合、フラグを追加する
        if ($arrProduct['product_type_id'] == PLG_PRODUCT_TYPE_SAMPLE) {
            $arrProduct['product_type_id'] = PRODUCT_TYPE_NORMAL;
            $arrProduct['sample_flg'] = true;
        } else {
            $arrProduct['sample_flg'] = false;
        }

        return $arrProduct;

    }

}
