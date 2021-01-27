<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
validate_user();
$authorization = get_auth_types($_SESSION['auth_type']);

// if auth_type is not administrator and not rtemanager then abort to main page
if ( $authorization['administrator'] === false && $authorization['rtemanager'] === false )
  {
    header("Location: index.php");
    exit;
  }
include("classes/delivery.class.php");
include("template_hdr.php");
echo '<div align="center">';
if ( $_POST )
  {
    Delivery::changeUserDeliveryInfo();
    $r = preg_replace("/[^0-9]/","",$_POST['r']);
    $d = preg_replace("/[^a-zA-Z0-9]/","",$_POST['d']);
    echo '<h3>Collection Location updated</h3> <a href="delivery.php">Return to Orders by Collection Location</a>.';
  }
else
  {
    $member_id = preg_replace("/[^0-9]/","",$_REQUEST['member_id']);
    $basket_id = preg_replace("/[^0-9]/","",$_REQUEST['basket_id']);
    if ( $member_id > 0 && $basket_id > 0 )
      {
        $sql = "SELECT delivery_id FROM ".TABLE_BASKET_ALL." WHERE basket_id = ".$basket_id." AND member_id = ".$member_id." LIMIT 1";
        $result = mysql_query($sql) or die(mysql_error()." ".$sql);
        $row = mysql_fetch_array($result);
        // See if this basket is in the current delivery cycle, otherwise, no updating.
        if  ($row['delivery_id'] > 0 && $row['delivery_id'] == $_SESSION['current_delivery_id'] )
          {
            echo '
              <h3>Change Collection Location - basket #'.$basket_id.', member #'.$member_id.'</h3><br />';
            echo Delivery::printChangeDeliveryInfoForm($basket_id,$member_id);
          }
        else
          {
            echo '
              This basket&#146;s delivery can no longer be changed.
              It was in delivery cycle '.$row['delivery_id'].' and only baskets in delivery cycle '.$_SESSION['current_delivery_id'].' can be edited at this time.';
          }
      }
  }
echo '</div>';
include("template_footer.php");
?>