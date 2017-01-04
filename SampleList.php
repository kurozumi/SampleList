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
 *
 *
 */

/**
 * プラグインの基底クラス
 *
 * @package Plugin
 * @author LOCKON CO.,LTD.
 * @version $Id: $
 */
class SampleList extends SC_Plugin_Base
{
    /**
     * コンストラクタ
     *
     * @param  array $arrSelfInfo 自身のプラグイン情報
     * @return void
     */
    public function __construct(array $arrSelfInfo)
    {
        if ($arrSelfInfo["enable"] == 1) {
            define("PLG_PRODUCT_TYPE_SAMPLE", $arrSelfInfo["free_field2"]);
            define("PLG_SAMPLE_URLPATH", ROOT_URLPATH . "sample");
        }

    }

    /**
     * インストール
     * installはプラグインのインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
     * @return void
     */
    public function install($arrPlugin, $objPluginInstaller = null)
    {
        $page_id = array(
            DEVICE_TYPE_PC => self::doRegister(DEVICE_TYPE_PC),
            DEVICE_TYPE_SMARTPHONE => self::doRegister(DEVICE_TYPE_SMARTPHONE));

        // プラグイン情報にページIDを保持しておく
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->update("dtb_plugin", array("free_field1" => serialize($page_id)), "plugin_code = ?", array($arrPlugin["plugin_code"]));

    }

    /**
     * アンインストール
     * uninstallはアンインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function uninstall($arrPlugin, $objPluginInstaller = null)
    {
        $page_id = unserialize($arrPlugin["free_field1"]);

        $objLayout = new SC_Helper_PageLayout_Ex();
        $objLayout->lfDelPageData($page_id[DEVICE_TYPE_PC], DEVICE_TYPE_PC);
        $objLayout->lfDelPageData($page_id[DEVICE_TYPE_SMARTPHONE], DEVICE_TYPE_SMARTPHONE);

        SC_Helper_FileManager_Ex::deleteFile(HTML_REALDIR . "sample");
        SC_Helper_FileManager_Ex::deleteFile(TEMPLATE_REALDIR . "sample");
        SC_Helper_FileManager_Ex::deleteFile(SMARTPHONE_TEMPLATE_REALDIR . "sample");

    }

    /**
     * 稼働
     * enableはプラグインを有効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function enable($arrPlugin, $objPluginInstaller = null)
    {
        $masterData = new SC_DB_MasterData_Ex();

        $objQuery = & SC_Query_Ex::getSingletonInstance();

        $objQuery->begin();

        $product_type_id = self::getNextMasterDataId("mtb_product_type");

        // サンプル商品用の商品種別IDを登録
        $objQuery->insert("mtb_product_type", array(
            'id' => $product_type_id,
            'name' => "サンプル商品",
            'rank' => self::getNextMasterDataRank("mtb_product_type")));

        $masterData->clearCache("mtb_product_type");

        // プラグイン情報にサンプル商品用の商品種別IDを保持しておく
        $objQuery->update("dtb_plugin", array("free_field2" => $product_type_id), "plugin_code = ?", array($arrPlugin["plugin_code"]));

        $objQuery->commit();

    }

    /**
     * 停止
     * disableはプラグインを無効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function disable($arrPlugin, $objPluginInstaller = null)
    {
        $masterData = new SC_DB_MasterData_Ex();

        $objQuery = & SC_Query_Ex::getSingletonInstance();

        $objQuery->begin();

        // サンプル商品用の商品種別IDを削除
        $objQuery->delete("mtb_product_type", "id=?", array($arrPlugin["free_field2"]));

        $masterData->clearCache("mtb_product_type");

        $objQuery->update("dtb_plugin", array("free_field2" => null), "plugin_code = ?", array($arrPlugin["plugin_code"]));

        $objQuery->setGroupBy("product_id");
        $products = $objQuery->select("product_id", "dtb_products_class", "product_type_id=?", array(PLG_PRODUCT_TYPE_SAMPLE));

        // サンプル商品の非表示してproduct_type_idを通常商品へ変更する
        foreach ($products as $product) {
            $objQuery->update("dtb_products", array("status" => 2), "product_id=?", array($product["product_id"]));
            $objQuery->update("dtb_products_class", array("product_type_id" => 1), "product_id=?", array($product["product_id"]));
        }
        $objQuery->commit();

    }

    /**
     * プラグインヘルパーへ, コールバックメソッドを登録します.
     *
     * @param integer $priority
     */
    public function register(SC_Helper_Plugin $objHelperPlugin, $priority)
    {
        $objHelperPlugin->addAction("loadClassFileChange", array(&$this, "loadClassFileChange"), $priority);
        $objHelperPlugin->addAction("prefilterTransform", array(&$this, "prefilterTransform"), $priority);
        $objHelperPlugin->addAction("LC_Page_Shopping_action_before", array(&$this, "shopping_action_before"), $priority);

    }

    /**
     * SC_系のクラスをフックする
     * 
     * @param type $classname
     * @param type $classpath
     */
    public function loadClassFileChange(&$classname, &$classpath)
    {
        $base_path = PLUGIN_UPLOAD_REALDIR . basename(__DIR__) . "/data/class/";

        if ($classname == "SC_CartSession_Ex") {
            $classname = "plg_SampleList_SC_CartSession";
            $classpath = $base_path . $classname . ".php";
        }
        if ($classname == "SC_Product_Ex") {
            $classname = "plg_SampleList_SC_Product";
            $classpath = $base_path . $classname . ".php";
        }

    }

    public function shopping_action_before($objPage)
    {
        $objCartSess = new SC_CartSession_Ex();
        $objCustomer = new SC_Customer_Ex();

        $cartKey = $objCartSess->getKey();

        // ログイン済みの場合は次画面に遷移
        if ($objCustomer->isLoginSuccess(true)) {
            SC_Response_Ex::sendRedirect(
                    $this->getNextlocation($cartKey, $objPage));
            SC_Response_Ex::actionExit();
        }

    }

    /**
     * ログイン済みの場合の遷移先を取得する.
     * 
     * @param type $product_type_id
     * @param type $objPage
     * @return string
     */
    public function getNextLocation($product_type_id, $objPage)
    {
        switch ($product_type_id) {
            case PRODUCT_TYPE_NORMAL:
                if (method_exists('SC_Customer_Ex', 'isB2B') && SC_Customer_Ex::isB2B() === true)
                    return 'deliv.php';

                if ($objPage->getMode() == "deliv")
                    return 'deliv.php';

                return PLG_SAMPLE_URLPATH;
        }

    }

    /**
     * テンプレートをフックする
     *
     * @param string &$source
     * @param LC_Page_Ex $objPage
     * @param string $filename
     * @return void
     */
    public function prefilterTransform(&$source, LC_Page_Ex $objPage, $filename)
    {
        $objTransform = new SC_Helper_Transform($source);
        $template_dir = PLUGIN_UPLOAD_REALDIR . basename(__DIR__) . "/data/templates/";
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_MOBILE:
                break;
            case DEVICE_TYPE_SMARTPHONE:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                break;
        }
        $source = $objTransform->getHTML();

    }

    /**
     * テンプレートをフックする
     *
     * @param string &$source
     * @param LC_Page_Ex $objPage
     * @param string $filename
     * @return void
     */
    public function outputfilterTransform(&$source, LC_Page_Ex $objPage, $filename)
    {
        $objTransform = new SC_Helper_Transform($source);
        $template_dir = PLUGIN_UPLOAD_REALDIR . basename(__DIR__) . "/data/templates/";
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_MOBILE:
                break;
            case DEVICE_TYPE_SMARTPHONE:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                break;
        }
        $source = $objTransform->getHTML();

    }

    /**
     * 次に割り当てるMasterDataのIDを取得する
     * 
     * @param type $name
     * @return type
     */
    public static function getNextMasterDataId($name)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        return $objQuery->max("id", $name) + 1;

    }

    /**
     * 次に割り当てるMasterDataのRANKを取得する
     * 
     * @param type $name
     * @return type
     */
    public static function getNextMasterDataRank($name)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();
        return $objQuery->max("rank", $name) + 1;

    }

    /**
     * ファイルの作成に失敗した場合は, エラーメッセージを出力し,
     * データベースをロールバックする.
     * 
     * @param type $device_type_id
     * @return integer|boolean
     */
    public static function doRegister($device_type_id = 10, $page_id = "")
    {
        $objLayout = new SC_Helper_PageLayout_Ex();

        $arrParams['device_type_id'] = $device_type_id;
        $arrParams['page_id'] = $page_id;
        $arrParams['header_chk'] = 1;
        $arrParams['footer_chk'] = 1;
        $arrParams['edit_flg'] = 2;
        $arrParams['page_name'] = 'サンプル商品一覧ページ';
        $arrParams['url'] = 'sample/index.php';
        $arrParams['filename'] = 'sample/index';

        $objQuery = & SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        $page_id = self::registerPage($arrParams);

        /*
         * 新規登録時
         * or 編集可能な既存ページ編集時かつ, PHP ファイルが存在しない場合に,
         * PHP ファイルを作成する.
         */
        if (SC_Utils_Ex::isBlank($arrParams['page_id']) || $objLayout->isEditablePage($arrParams['device_type_id'], $arrParams['page_id'])) {
            if (!self::createPHPFile($arrParams['filename'])) {
                $objQuery->rollback();

                return false;
            }
            // 新規登録時のみ $page_id を代入
            $arrParams['page_id'] = $page_id;
        }

        $tpl_path = $objLayout->getTemplatePath($arrParams['device_type_id']) . $arrParams['filename'] . '.tpl';

        if (!self::createTPLFile($tpl_path, $device_type_id)) {
            $objQuery->rollback();

            return false;
        }

        $objQuery->commit();

        return $arrParams['page_id'];

    }

    /**
     * 入力内容をデータベースに登録する.
     *
     * @param  array                $arrParams フォームパラメーターの配列
     * @return integer              ページID
     */
    public static function registerPage($arrParams)
    {
        $objQuery = & SC_Query_Ex::getSingletonInstance();

        $table = 'dtb_pagelayout';
        $arrValues = $objQuery->extractOnlyColsOf($table, $arrParams);
        $arrValues['update_date'] = 'CURRENT_TIMESTAMP';

        $objQuery->setOrder('');
        $arrValues['page_id'] = 1 + $objQuery->max('page_id', $table, 'device_type_id = ?', array($arrValues['device_type_id']));
        $arrValues['create_date'] = 'CURRENT_TIMESTAMP';
        $objQuery->insert($table, $arrValues);

        return $arrValues['page_id'];

    }

    /**
     * PHP ファイルを生成する.
     *
     * 既に同名の PHP ファイルが存在する場合は何もせず true を返す.(#831)
     *
     * @param  string  $filename フォームパラメーターの filename
     * @return boolean 作成に成功した場合 true
     */
    public static function createPHPFile($filename)
    {
        $path = HTML_REALDIR . $filename . '.php';

        if (file_exists($path)) {
            return true;
        }

        if (mkdir(HTML_REALDIR . "sample")) {
            return copy(PLUGIN_UPLOAD_REALDIR . "SampleList/html/sample/index.php", $path);
        }

        return false;

    }

    /**
     * TPL ファイルを作成する
     * 
     * @param type $tpl_path
     * @param type $device_type_id
     * @return boolean
     */
    public static function createTPLFile($tpl_path, $device_type_id)
    {
        if (file_exists($tpl_path)) {
            return true;
        }

        switch ($device_type_id) {
            case DEVICE_TYPE_MOBILE:
                $tpl_dir = MOBILE_TEMPLATE_REALDIR . "sample";
                break;

            case DEVICE_TYPE_SMARTPHONE:
                $tpl_dir = SMARTPHONE_TEMPLATE_REALDIR . "sample";
                break;

            case DEVICE_TYPE_PC:
            default:
                $tpl_dir = TEMPLATE_REALDIR . "sample";
                break;
        }

        if (mkdir($tpl_dir)) {
            return copy(PLUGIN_UPLOAD_REALDIR . "SampleList/data/templates/default/sample/index.tpl", $tpl_path);
        }

        return false;

    }

}
