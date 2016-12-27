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

require_once CLASS_REALDIR . 'SC_CartSession.php';

class plg_SampleList_SC_CartSession extends SC_CartSession
{
    /**
     * カート内の商品の妥当性をチェックする.
     *
     * エラーが発生した場合は, 商品をカート内から削除又は数量を調整し,
     * エラーメッセージを返す.
     *
     * 1. 商品種別に関連づけられた配送業者の存在チェック
     * 2. 削除/非表示商品のチェック
     * 3. 販売制限数のチェック
     * 4. 在庫数チェック
     *
     * @param  string $productTypeId 商品種別ID
     * @return string エラーが発生した場合はエラーメッセージ
     */
    public function checkProducts($productTypeId)
    {
        $tpl_message = parent::checkProducts($productTypeId);

        if (PRODUCT_TYPE_NORMAL != $productTypeId)
            return $tpl_message;

        // カート内の情報を取得
        $cartList = $this->getCartList($productTypeId);
        $arrItems = array_filter($cartList, array($this, "isNotSample"));

        if (count($arrItems) == 0) {
            foreach ($cartList as &$arrItem) {
                $product = & $arrItem['productsClass'];

                $tpl_message .= sprintf("※ サンプル商品以外の商品がカートに入っていないため、%sは購入できません。\n", $product["name"]);
                $this->delProduct($arrItem['cart_no'], $productTypeId);
            }
        }
        
        // カート内の情報を取得
        $arrItems = $this->getCartList($productTypeId);
        foreach ($arrItems as &$arrItem) {
            
            if($this->isNotSample($arrItem))
                continue;
            
            $product = & $arrItem['productsClass'];

            // 購入回数を取得
            $count = $this->getBuyCount($product);

            if ($count >= 1) {
                $tpl_message .= sprintf("※ %sは一度購入しています。\n", $product["name"]);
                $this->delProduct($arrItem['cart_no'], $productTypeId);
            }
        }

        return $tpl_message;

    }

    // 商品の削除
    public function delProduct($cart_no, $productTypeId)
    {
        $max = $this->getMax($productTypeId);
        for ($i = 0; $i <= $max; $i++) {
            if ($this->cartSession[$productTypeId][$i]['cart_no'] == $cart_no) {
                unset($this->cartSession[$productTypeId][$i]);
            }
        }
        
        $cartList = $this->getCartList($productTypeId);
        $arrItems = array_filter($cartList, array($this, "isNotSample"));

        // 通常商品がない場合、カートを空にする
        if (count($arrItems) == 0) {
            foreach ($cartList as &$arrItem) {
                $this->delProduct($arrItem['cart_no'], $productTypeId);
            }
        }

    }

    /**
     * サンプル商品ではないかどうか
     * 
     * @param type $arrItem
     * @return type
     */
    public function isNotSample($arrItem)
    {
        return !$arrItem['productsClass']['sample_flg'];

    }
    
    /**
     * 購入回数を取得
     * 
     * @param array $p
     * @return int
     */
    public function getBuyCount($p)
    {
        $objCustomer = new SC_Customer_Ex();
        $objQuery = SC_Query_Ex::getSingletonInstance();

        $customer_id = $objCustomer->getValue('customer_id');

        $where = "O.customer_id = ? AND OD.product_id = ?";
        $arrWhereVal = array($customer_id, $p["product_id"]);

        $objQuery->setGroupBy("O.order_id");
        return (int) $objQuery->count("dtb_order_detail AS OD JOIN dtb_order as O", $where, $arrWhereVal);

    }

}
