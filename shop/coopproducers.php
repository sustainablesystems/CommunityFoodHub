<?php
include_once ('config_foodcoop.php');

$color1 = "#DDDDDD";
$color2 = "#CCCCCC";
$row_count = 0;

$sqlr = '
  SELECT
    '.TABLE_PRODUCER.'.*,
    '.TABLE_MEMBER.'.*,
    '.TABLE_PRODUCER_REG.'.website
  FROM
    '.TABLE_PRODUCER.',
    '.TABLE_MEMBER.',
    '.TABLE_PRODUCER_REG.'
  WHERE
    '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.TABLE_PRODUCER_REG.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.TABLE_PRODUCER.'.donotlist_producer != "1"
    AND '.TABLE_PRODUCER.'.pending = "0"
  ORDER BY
    '.TABLE_MEMBER.'.business_name ASC';
$rsr = @mysql_query($sqlr,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_array($rsr) )
  {
    $producer_id = $row['producer_id'];
    $business_name = stripslashes($row['business_name']);
    $producttypes = stripslashes($row['producttypes']);
    $is_supplier = $row['is_supplier'];

    $first_name = stripslashes($row['first_name']);
    $last_name = stripslashes($row['last_name']);
    $first_name_2 = stripslashes($row['first_name_2']);
    $last_name_2 = stripslashes($row['last_name_2']);

    $website = stripslashes($row['website']);

    $producer_id_lower = strtolower($producer_id);

    $row_color = ($row_count % 2) ? $color1 : $color2;

    $display_top .= '
      <tr bgcolor="'.$row_color.'">
        <td width="25%"><font face="arial" size="3"><b>';

    if ($is_supplier)
    {
      // Display name, with link to external website (if present)
      $display_top .= 
        ($website ? '<a href="http://'.$website.'">' : '')
        .$business_name
        .($website ? '</a>' : '');
    }
    else // Producer
    {
      // Display name, with link to producer page
      $display_top .=
        '<a href="producers/'.$producer_id.'.php">'
        .$business_name
        .'</a>';
    }

    $display_top .= '</b></td>
        <td width="75%">'.$producttypes.'</font></td>
      </tr>';

    $row_count++;
  }

?>

  <!-- CONTENT BEGINS HERE -->
<?php
// User may be logged on already

include("template_hdr.php");
?>
  
<h1><?php echo SITE_NAME; ?> Suppliers</h1>

<table cellpadding="2" cellspacing="2" border="0" align="center" width="800">
  <tr bgcolor="#AEDE86">
    <td><b>Supplier Name</b></td><td><b>Types of Product</b></td>
  </tr>
  <?php echo $display_top;?>
</table>


  <!-- CONTENT ENDS HERE -->

<?php include("template_footer.php");?>