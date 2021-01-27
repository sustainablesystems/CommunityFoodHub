<?php

// Place a heading for the producer at the top of the table if needed
// TODO: Not sure $is_producer_heading is needed - don't we always want the heading
// for proper producers?
if ( /*$is_producer_heading &&*/ !$is_supplier )
{
    $display .= "
        <font color=\"#770000\">
            <$heading_size>
                <a href='".BASE_URL.PATH.'producers/'.strtolower($producer_id).".php"
                    .($is_logged_in ? "?members=true" : "")."'>
                    $business_name
                </a>
            </$heading_size>
        </font>";
}

$display .= '
  <table border="1" cellpadding="5" cellspacing="0" bordercolor="#DDDDDD" bgcolor="#ffffff" width="95%" align="center">
    <tr>';
$display .= "<th align=center bgcolor=#DDDDDD width=\"60\">ID</th>";
$display .= "<th align=center bgcolor=#DDDDDD>Product Name and Description";
if ( !$is_supplier )
{
    $display .= ' [<a href="'.BASE_URL.PATH.'producers/'.strtolower($producer_id).'.php'
      .($is_logged_in ? '?members=true' : '').'">About Producer</a>]';
}
$display .= "</th>";
if ( $is_supplier && !$is_compound )
{
    $display .= "<th align=center bgcolor=#DDDDDD width=\"100\">Origin</th>";
}
$display .= "<th align=center bgcolor=#DDDDDD width=\"60\">Status</th>";
if ($is_subproduct)
{
    $display .= "<th align=center bgcolor=#DDDDDD width=\"60\">Quantity</th>";
}
else
{
    $display .= "<th align=center bgcolor=#DDDDDD width=\"60\">Unit</th>";
    $display .= "<th align=center bgcolor=#DDDDDD width=\"60\">Price</th>";
}

if ( $display_type == 'shop' )
  {
    $display .= '
      <th align="center" bgcolor="#DDDDDD" width="60">Order</th>';
  }
elseif ( $display_type == 'new_or_changed' )
  {
    $display .= '
      <th align="center" bgcolor="#DDDDDD" width="60"></th>';
  }
elseif ( $display_type == 'edit' )
  {
    $display .= '
      <th align="center" bgcolor="#DDDDDD" width="60">Edit</th>';
  }
else
  {
      // Empty
  }

$display .= "</tr>";
?>