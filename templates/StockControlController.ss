<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Update Your Stock</title>
</head>
<body>

<% if StockProductObjects %>
<div class="StockObjectsFeedback"><p>Please adjust available quantities below.</p></div>
<ul id="StockProductObjects">
<% loop StockProductObjects %>
	<li>
		<span class="currentNumber item$ID">$BaseQuantity =</span>
		<input type="text" value="" name="update/{$ID}" class="updateField productUpdateField" />
		<label class="history"><a href="$HistoryLink" rel="history{$BuyableClassName}{$BuyableID}">$Name</a></label>
		<div class="loadHistoryHere" id="history{$BuyableClassName}{$BuyableID}"></div>
	</li>
<% end_loop %>
<% if StockProductObjects.MoreThanOnePage %>
	<% if StockProductObjects.NotFirstPage %>
		<a class="prev" href="/$StockProductObjects.PrevLink">Prev</a>
	<% end_if %>
 <% loop StockProductObjects.PaginationSummary(10) %>
		<% if CurrentBool %>
			$PageNum
		<% else %>
			<a href="/$Link">$PageNum</a>
		<% end_if %>
	<% end_loop %>
	<% if StockProductObjects.NotLastPage %>
		<a class="next" href="/$StockProductObjects.NextLink">Next</a>
	<% end_if %>
<% end_if %>
</ul>
<div class="StockObjectsFeedback"><p>Please adjust available quantities above.</p></div>

<% else %>
<p>There are no stock quantities to adjust.</p>
<% end_if %>

</body>
</html>
