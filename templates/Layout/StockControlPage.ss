$Content
$Form
<div class="StockObjectsFeedback"><p>Please adjust available quantities below.</p></div>
<ul id="StockProductObjects">
<% control StockProductObjects %>
	<li>
		<span class="currentNumber">$CalculatedQuantity =</span>
		<input type="text" value="" name="$ClassName/$ID" class="updateField productUpdateField" />
		<label class="history"><a href="{$StockControlPage.Link}history/$ID" rel="history{$ClassName}{$ID}">$Name</a></label>
		<div class="loadHistoryHere" id="history{$ClassName}{$ID}"></div>
	</li>
<% end_control %>
</ul>
<div class="StockObjectsFeedback"><p>Please adjust available quantities above.</p></div>
