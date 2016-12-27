<?php

/*
 * MakerBlock
 * Copyright (C) 2013 BLUE STYLE All Rights Reserved.
 * http://bluestyle.jp/
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/LC_Page_Ex.php';

/**
 * メーカー のページクラス.
 *
 * @package Page
 * @author BLUE STYLE
 * @version $Id: $
 */
class LC_Page_Sample extends LC_Page_Ex
{
    const PLUGIN_NAME = "SampleList";

    /** ページレイアウトをスキップする */
    public $skip_load_page_layout = true;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init()
    {
        parent::init();

        $this->tpl_mainpage = "sample/index.tpl";

        $plugin = SC_Plugin_Util_Ex::getPluginByPluginCode(self::PLUGIN_NAME);
        $this->disp_number = $plugin["free_field3"];

    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process()
    {
        parent::process();
        $this->action();
        $this->sendResponse();

    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    public function action()
    {
        //決済処理中ステータスのロールバック
        $objPurchase = new SC_Helper_Purchase_Ex();
        $objPurchase->cancelPendingOrder(PENDING_ORDER_CANCEL_FLAG);

        $objProduct = new SC_Product_Ex();
        // パラメーター管理クラス
        $objFormParam = new SC_FormParam_Ex();

        $objCartSess = new SC_CartSession_Ex();

        // パラメーター情報の初期化
        $this->lfInitParam($objFormParam);

        // 値の設定
        $objFormParam->setParam($_REQUEST);

        // 入力値の変換
        $objFormParam->convParam();

        // 値の取得
        $this->arrForm = $objFormParam->getHashArray();

        //modeの取得
        $this->mode = $this->getMode();

        // 商品一覧データの取得
        $arrSearchCondition = $this->lfGetSearchCondition();

        $this->arrProducts = $this->lfGetProductsList($arrSearchCondition, $objProduct);


        switch ($this->getMode()) {
            case 'cart':
                $objCartSess = new SC_CartSession_Ex();

                // カート内のサンプル商品を削除
                foreach ($objCartSess->getCartList(PRODUCT_TYPE_NORMAL) as $product) {
                    if ($product["productsClass"]["sample_flg"])
                        $objCartSess->delProduct($product["cart_no"], PRODUCT_TYPE_NORMAL);
                }

                $this->doCart($objProduct, $objFormParam);

                break;
            default:
                break;
        }

        $this->doDefault($objProduct, $objFormParam);

    }

    /**
     * パラメーター情報の初期化
     *
     * @param  SC_FormParam_Ex $objFormParam フォームパラメータークラス
     * @return void
     */
    public function lfInitParam(&$objFormParam)
    {
        // カートイン
        $objFormParam->addParam('サンプル商品', 'product', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));

    }

    /* 商品一覧の表示 */
    /**
     * @param SC_Product_Ex $objProduct
     */
    public function lfGetProductsList($searchCondition, &$objProduct)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();

        // 取得範囲の指定(開始行番号、行数のセット)
        $objQuery->setWhere($searchCondition['where']);

        // 表示すべきIDとそのIDの並び順を一気に取得
        $arrProductId = $objProduct->findProductIdsOrder($objQuery, $searchCondition['arrval']);

        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $arrProducts = $objProduct->getListByProductIds($objQuery, $arrProductId);

        // 規格を設定
        $objProduct->setProductsClassByProductIds($arrProductId);
        $arrProducts['productStatus'] = $objProduct->getProductStatus($arrProductId);

        return $arrProducts;

    }

    /* 入力内容のチェック */
    /**
     * @param SC_FormParam_Ex $objFormParam
     */
    public function lfCheckError($objFormParam)
    {
        // 入力データを渡す。
        $arrForm = $objFormParam->getHashArray();
        $objErr = new SC_CheckError_Ex($arrForm);
        $objErr->arrErr = $objFormParam->checkError();

        return $objErr->arrErr;

    }

    public function checkProducts($productTypeId)
    {
        $arrErr = array();
        $objCartSess = new SC_CartSession_Ex();
        $tpl_message = $objCartSess->checkProducts($productTypeId);

        if (!SC_Utils_Ex::isBlank($tpl_message)) {
            $arrErr['product'] = $tpl_message;
        }

        return $arrErr;

    }

    /**
     * 検索条件のwhere文とかを取得
     *
     * @return array
     */
    public function lfGetSearchCondition()
    {
        $searchCondition = array(
            'where' => '',
            'arrval' => array()
        );

        // ▼対象商品IDの抽出
        // 商品検索条件の作成（未削除、表示）
        $searchCondition['where'] = SC_Product_Ex::getProductDispConditions('alldtl');

        // 商品種別がPLG_PRODUCT_TYPE_SAMPLEを抽出
        $searchCondition['where'] .= ' AND EXISTS(SELECT * FROM dtb_products_class WHERE product_id = alldtl.product_id AND product_type_id = ?)';
        $searchCondition['arrval'][] = PLG_PRODUCT_TYPE_SAMPLE;

        // 在庫無し商品の非表示
        if (NOSTOCK_HIDDEN) {
            $searchCondition['where'] .= ' AND EXISTS(SELECT * FROM dtb_products_class WHERE product_id = alldtl.product_id AND del_flg = 0 AND (stock >= 1 OR stock_unlimited = 1))';
        }

        // XXX 一時期内容が異なっていたことがあるので別要素にも格納している。
        $searchCondition['where_for_count'] = $searchCondition['where'];

        return $searchCondition;

    }

    /**
     *
     * @param  SC_Product_Ex $objProduct
     * @param SC_FormParam_Ex $objFormParam
     * @return void
     */
    public function doDefault(&$objProduct, &$objFormParam)
    {
        $this->tpl_stock_find = $objProduct->stock_find;
        $this->tpl_product_class_id = $objProduct->product_class_id;

        // 商品ステータスを取得
        $this->productStatus = $this->arrProducts['productStatus'];
        unset($this->arrProducts['productStatus']);

        $objCartSess = new SC_CartSession_Ex();

        // カート内の通常商品のproduct_class_idリストを取得
        foreach ($objCartSess->getAllProductClassID(PRODUCT_TYPE_NORMAL) as $product_class_id) {
            if (isset($this->arrProducts[$product_class_id])) {
                $this->arrProducts[$product_class_id]["selected"] = true;
            }
        }

        // カート「戻るボタン」用に保持
        $netURL = new Net_URL();
        //該当メソッドが無いため、$_SESSIONに直接セット
        $_SESSION['cart_referer_url'] = $netURL->getURL();

    }

    /**
     * Add product(s) into the cart.
     *
     * @return void
     */
    public function doCart(&$objProduct, &$objFormParam)
    {
        $this->arrErr = $this->lfCheckError($objFormParam);

        if (count($this->arrErr) == 0) {
            $objCartSess = new SC_CartSession_Ex();
            $objSiteSess = new SC_SiteSession_Ex();

            // カートにサンプル商品を追加
            $products = $objFormParam->getValue("product");
            foreach ($products as $product_id) {
                $objCartSess->addProduct($product_id, 1);
            }

            $this->arrErr = $this->checkProducts(PRODUCT_TYPE_NORMAL);

            // エラーがなければお届け先の指定へ
            if (count($this->arrErr) == 0) {
                // カート内商品リストを取得
                $cartList = $objCartSess->getCartList(PRODUCT_TYPE_NORMAL);
                if (count($cartList) > 0) {
                    // カートを購入モードに設定
                    $this->lfSetCurrentCart($objSiteSess, $objCartSess, PRODUCT_TYPE_NORMAL);
                }

                // 在庫チェック
                foreach ($cartList as $arrItem) {
                    $limit = $objProduct->getBuyLimit($arrItem['productsClass']);
                    if (!is_null($limit) && $arrItem['quantity'] > $limit) {
                        SC_Response_Ex::sendRedirect(PLG_SAMPLE_URLPATH);
                        SC_Response_Ex::actionExit();
                    }
                }

                SC_Response_Ex::sendRedirect(DELIV_URLPATH);
                SC_Response_Ex::actionExit();
            }
        }

    }

    /**
     * order_temp_id の更新
     *
     * @return
     */
    public function lfUpdateOrderTempid($pre_uniqid, $uniqid)
    {
        $sqlval['order_temp_id'] = $uniqid;
        $where = 'order_temp_id = ?';
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $res = $objQuery->update('dtb_order_temp', $sqlval, $where, array($pre_uniqid));
        if ($res != 1) {
            return false;
        }

        return true;

    }

    /**
     * カートを購入モードに設定
     *
     * @param SC_SiteSession_Ex $objSiteSess
     * @param SC_CartSession_Ex $objCartSess
     * @return void
     */
    public function lfSetCurrentCart(&$objSiteSess, &$objCartSess, $cartKey)
    {
        // 正常に登録されたことを記録しておく
        $objSiteSess->setRegistFlag();
        $pre_uniqid = $objSiteSess->getUniqId();
        // 注文一時IDの発行
        $objSiteSess->setUniqId();
        $uniqid = $objSiteSess->getUniqId();
        // エラーリトライなどで既にuniqidが存在する場合は、設定を引き継ぐ
        if ($pre_uniqid != '') {
            $this->lfUpdateOrderTempid($pre_uniqid, $uniqid);
        }
        // カートを購入モードに設定
        $objCartSess->registerKey($cartKey);
        $objCartSess->saveCurrentCart($uniqid, $cartKey);

    }

}

?>
