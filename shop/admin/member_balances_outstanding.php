<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

$display .= '<h1 align="center">Customer Balances Outstanding</h1>';

$sql = '
  SELECT
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.curr_balance
  FROM
    '.TABLE_MEMBER.'
  WHERE
    '.TABLE_MEMBER.'.pending = "0"
    AND '.TABLE_MEMBER.'.membership_discontinued != "1"
    AND '.TABLE_MEMBER.'.curr_balance != "0.00"
  ORDER BY
    last_name ASC,
    first_name ASC';

$rs = @mysql_query($sql,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$num = mysql_numrows($rs);

if ($num == 0)
{
  $display .= '<p align="center"><i>No customer balances outstanding.</i></p>';
}
else
{
  $display .= '<div width="800"><p align="center">
    <font color="red">Positive balances indicate money owed to '.SITE_NAME_SHORT.'; negative balances indicate credit.</font>
    </p></div>';

  $display .= '<table align="center" width="800" cellspacing="2" cellpadding="2" border="1">';
  $display .= '<tr><th>Customer</th><th>Balance</th><th>&nbsp;</th>';
  
  while ( $row = mysql_fetch_array($rs) )
  {
    $member_id = $row['member_id'];
    $first_name = stripslashes ($row['first_name']);
    $last_name = stripslashes ($row['last_name']);
    $curr_balance = stripslashes ($row['curr_balance']);

    $display .= '<tr><td>'.$last_name.', '.$first_name.'</td>';
    $display .= '<td align="center">'.CURSYM.' '.$curr_balance.'</td>';
    $display .= '<td align="center"><a href="adjustments.php?adj_type=customer&customer_id='.$member_id.'">View/Apply Adjustments</a></td></tr>';
  }

  $display .= '</table>';
}
?>

<?php include("template_hdr.php"); ?>
<!-- CONTENT BEGINS HERE -->

<?php echo $display;?>

<!-- CONTENT ENDS HERE -->
<?php include("template_footer.php"); ?>