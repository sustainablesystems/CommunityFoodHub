<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

include("template_hdr.php");

include_once("../func/collection_funcs.php");
$collection_location_form = new CollectionLocationForm($connection);

// Get the delcode_id being edited (may be null if we're adding a new location)
$edit_delcode_id = $_GET['delcode_id'];

switch ( $_POST['action'] )
{
  case 'Add New Location':
    $collection_location_form->startAdd();
    break;

  case 'Edit':
    if (null == $_POST['delcode_id'])
    {
      $display .= '<p align="center"><font color="red"><b>Please select a collection location to edit.</b></font></p>';
    }
    else
    {
      $collection_location_form->startEdit( $_POST['delcode_id'] );
    }
    break;

  case 'Save':
  case 'Save As New':
    $display .= $collection_location_form->saveLocation( $edit_delcode_id, $_POST, true );
    break;

  case 'Update':
    $display .= $collection_location_form->saveLocation( $edit_delcode_id, $_POST, false );
    break;

  default:
    // If this isn't a form submission, but the URL contains a collection location anyway, edit it
    if (null != $edit_delcode_id)
    {
      if ('AddNewLocation' == $edit_delcode_id)
      {
        $collection_location_form->startAdd();
      }
      else
      {
        $collection_location_form->startEdit( $edit_delcode_id );
      }
    }
    break;
}

$display .= $collection_location_form->display();

echo $display;

include("template_footer.php");
?>
