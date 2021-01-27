<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

require_once("classes/mi.class.php");
include("template_hdr.php");
echo '<br><div align="center">';
$mi = new memberInterface;
switch ( $_GET[action] )
  {
    case 'add':
      $mi->buildAddMember();
      break;
    case 'checkMemberForm':
      $mi->checkMemberForm();
      break;
    case 'edit':
      $mi->editUser();
      break;
    case 'find':
      $mi->findForm();
      break;
    case 'displayUsers':
      $mi->findUsers();
      break;
    default:
      $mi->mainMenu();
      break;
  }
echo '</div>';
include("template_footer.php");
?>
