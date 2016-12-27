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

// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';

class LC_Page_Plugin_SampleList_Config extends LC_Page_Admin_Ex
{
    /**
     * 初期化する.
     *
     * @return void
     */
    function init()
    {
        parent::init();

        $this->tpl_mainpage = PLUGIN_UPLOAD_REALDIR . "SampleList/data/templates/admin/config.tpl";
        $this->tpl_subtitle = "サンプルリストプラグイン";

    }

    /**
     * プロセス.
     *
     * @return void
     */
    function process()
    {
        $this->action();
        $this->sendResponse();

    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action()
    {
        $objFormParam = new SC_FormParam_Ex();
        $plugin = SC_Plugin_Util_Ex::getPluginByPluginCode("SampleList");
        $this->setTemplate($this->tpl_mainpage);
        
        if ($plugin['enable'] != 1) {
            $this->enable = false;
            return;
        }
        
        $this->enable = true;

        $this->lfInitParam($objFormParam);
        $objFormParam->setParam($_POST);
        $objFormParam->convParam();

        $arrForm = array();
        $mode = $this->getMode();
        switch ($mode) {
            // 登録
            case 'confirm':
                $arrForm = $objFormParam->getHashArray();

                $this->arrErr = $objFormParam->checkError();

                // エラーなしの場合にはデータを更新
                if (count($this->arrErr) == 0) {
                    // データ更新
                    $ret = $this->updateData($arrForm);
                    if ($ret) {
                        $this->tpl_onload = "alert('登録が完了しました。');";
                    }
                }
                break;
            default:
                $arrForm["disp_number"] = $plugin['free_field3'];
                if (!is_array($arrForm))
                    $arrForm = array();
                break;
        }

        $this->arrForm = $arrForm;

    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy()
    {
        parent::destroy();

    }

    /**
     * パラメーター情報の初期化
     *
     * @param object $objFormParam SC_FormParamインスタンス
     * @return void
     */
    function lfInitParam(&$objFormParam)
    {
        $objFormParam->addParam('表示件数', 'disp_number', INT_LEN, 'n', array('NUM_CHECK'));

    }

    function updateData($arrData)
    {
		$objQuery =& SC_Query_Ex::getSingletonInstance();
		return $objQuery->update("dtb_plugin",array("free_field3" => $arrData['disp_number']),"plugin_code = ?",array('SampleList'));
    }

}
