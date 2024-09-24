<%-- Override this layout in your project or theme --%>
{$Form}

<% if $Result %>
<% loop %>
    <h4><a href="{$Link}">{$Title}</a><h4>
    <p>{$Abstract}</p>
<% end_loop %>
<% else %>
<p>No results</p>
<% end_if %>
