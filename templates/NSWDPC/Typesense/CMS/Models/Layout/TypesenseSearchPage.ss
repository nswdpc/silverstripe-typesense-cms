<%-- Override this layout in your project or theme --%>
{$Form}

<% if $Results %>
    <% loop $Results  %>
        {$Me}
    <% end_loop %>
<% else %>
    <p>No results</p>
<% end_if %>
