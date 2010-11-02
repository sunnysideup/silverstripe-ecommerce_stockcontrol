$Content
$Form
<div class="StockObjectsFeedback"><p>Please adjust available quantities below.</p></div>
<ul id="StockProductObjects">
<% control StockProductObjects %>
	<li>
		<span class="currentNumber">$CalculatedQuantity =</span>
		<input type="text" value="" name="$ClassName/$ID" class="updateField productUpdateField" />
		<label class="history"><a href="{$StockControlPage.Link}history/product/$ID" rel="history{$ClassName}{$ID}">$Title</a></label>
		<div class="loadHistoryHere" id="history{$ClassName}{$ID}"></div>
		<% if VariationQuantities %><ul id="StockVariationObjects"><% control VariationQuantities %>
			<li>
				<span class="currentNumber">$CalculatedQuantity =</span>
				<input type="text" value="" name="$ClassName/$ID" class="updateField variationUpdateField" />
				<label class="history"><a href="{$StockControlPage.Link}history/variation/$ID" rel="history{$ClassName}{$ID}">$Title</a></label>
				<div class="loadHistoryHere" id="history{$ClassName}{$ID}"></div>
			</li>
		<% end_control %></ul><% end_if %>
	</li>
<% end_control %>
</ul>
<div class="StockObjectsFeedback"><p>Please adjust available quantities above.</p></div>
