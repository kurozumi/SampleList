<script>
$(document).ready(function(){
  var disp_number = <!--{$disp_number}-->
  var $count = $("input[type=checkbox]:checked").length;
  if($count >= disp_number) {
    var $not   = $("input[type=checkbox]").not(":checked");
    $not.attr("disabled", true);
  }
  
  $("#form1 input[type=checkbox]").click(function(){
    var $count = $("input[type=checkbox]:checked").length;
    var $not   = $("input[type=checkbox]").not(":checked");

    if($count >= disp_number) {
      $not.attr("disabled", true);
    }else{
      $not.attr("disabled", false);
    }
  });
});
</script>
<!--{if $arrErr.product}-->
    <p class="attention"><!--{$arrErr.product}--></p>
<!--{/if}-->
            
<form name="form1" action="?" id="form1" method="post">
	<input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
	<input type="hidden" name="mode" value="cart" />
	<input type="image" class="hover_change_image" src="<!--{$TPL_URLPATH|h}-->img/button/btn_buystep.jpg" alt="購入手続きへ" name="confirm" />
	<hr />
	<!--{foreach from=$arrProducts item=arrProduct name=arrProducts}-->
		<!--{assign var=id value=$arrProduct.product_id}-->
		<div class="list_area clearfix">
		<a name="product<!--{$id|h}-->"></a>
		<div class="listphoto">
		<!--★画像★-->
		<a href="<!--{$smarty.const.P_DETAIL_URLPATH}--><!--{$id|u}-->">
		<img src="<!--{$smarty.const.IMAGE_SAVE_URLPATH}--><!--{$arrProduct.main_list_image|sfNoImageMainList|h}-->" alt="<!--{$arrProduct.name|h}-->" class="picture" /></a>
		</div>

		<div class="listrightbloc">
		<!--★商品名★-->
		<h3>
		<a href="<!--{$smarty.const.P_DETAIL_URLPATH}--><!--{$id|u}-->"><!--{$arrProduct.name|h}--></a>
		</h3>

		<!--★コメント★-->
		<div class="listcomment"><!--{$arrProduct.main_list_comment|h|nl2br}--></div>
        <!--{if $tpl_stock_find[$id]}-->
        <div class="quantity">
            <!--{if $arrProduct.selected}-->
              <input type="checkbox" checked="checked" name="product[]" class="box" value="<!--{$tpl_product_class_id[$id]|h}-->" maxlength="<!--{$smarty.const.INT_LEN}-->" />
            <!--{else}-->
              <input type="checkbox" name="product[]" class="box" value="<!--{$tpl_product_class_id[$id]|h}-->" maxlength="<!--{$smarty.const.INT_LEN}-->" />
            <!--{/if}-->
            <!--{if $arrErr.quantity != ""}-->
                <br /><span class="attention"><!--{$arrErr.quantity}--></span>
            <!--{/if}-->
        </div>
        <!--{else}-->
            <p>申し訳ございませんが、只今品切れ中です。</p>
        <!--{/if}-->
		</div>
		</div>
		<!--{foreachelse}-->
		<!--{include file="frontparts/search_zero.tpl"}-->
	<!--{/foreach}-->
</form>