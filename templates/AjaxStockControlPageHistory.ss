<h3>history for $Name</h3>

<% if OrderEntries %>
<h4>Sales</h4>
<ul>
	<% control OrderEntries %>
	<li>
		<span class="quantity">$Quantity</span>
		<span class="date">on $LastEdited.Nice</span>
		<span class="explanation"> - Order # $OrderID</span>
		<span class="small"><% if IncludeInCurrentCalculation %>included in current calculations<% else %>no longer relevant to calculations<% end_if %></span>
	</li>
	<% end_control %>
</ul>
<% else %>
<h4>There are no sales yet</h4>
<% end_if %>

<% if ManualUpdates %>
<h4>Manual Adjustments</h4>
<ul>
	<% control ManualUpdates %>
	<li>
		<span class="quantity">$Quantity</span>
		<span class="date">on $LastEdited.Nice</span>
		<span class="explanation"> - updated by $Member.FirstName $Member.Surname</span>
	</li>
	<% end_control %>
</ul>
<% else %>
<h4>There are no manual adjustments</h4>
<% end_if %>

