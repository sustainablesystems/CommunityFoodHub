<?php
require_once("formvalidator.class.php");
include_once("../func/producer_funcs.php");

class memberInterface
{
  var $member;
  var $producer;

  function memberInterface()
  {
    $this->member="members";
    $this->producer="producers";
    return true;
  }

  function mainMenu()
  {
    echo "<div style='border:.5px black solid;width:300px;'>";
    echo "<a href='member_interface.php?action=find'>Find/Edit a Member/Producer</a><br>";
    echo "<a href='member_interface.php?action=add'>Add a New Member/Producer</a><br>";
    echo "<a href='edit_member_types.php'>Edit Member Types</a><br>";
    echo "</div>";
    return true;
  }

  function buildAddMember()
  {
    include "forms/addmember.form.php";
    return true;
  }

  function checkMemberForm()
  {
    $cf=new formValidator;
    
    $cf->checkText($_POST[first_name], "first name");
    $cf->checkText($_POST[last_name], "last name");

    $cf->validateEmail($_POST[email_address], "email address");

    // Check that the email address doesn't match anyone elses
    $query_string="SELECT * FROM ".TABLE_MEMBER." WHERE `email_address` = '".$_POST['email_address']."'"
      .($_GET[ID] ? " AND `member_id` <> '".$_GET[ID]."'" : "").";";
    $query=mysql_query($query_string);
    $rows=@mysql_num_rows($query);
    if($rows>0)
    {
      $cf->_errors[$cf->_counter]="There is already a member with this email address. Please choose a new one.";
      $cf->_counter++;
    }

    if($_POST[email_address]!=$_POST[email_address_2])
    {
      $cf->_errors[$cf->_counter]="The repeat email address does not match.";
      $cf->_counter++;
    }

    $cf->checkText($_POST[mobile_phone], "contact phone");

    // If this is a new member, or the password is being edited, check it
    if( !$_GET[ID] || $_GET[password] )
    {
      $cf->checkText($_POST[password], "password", "min", 6, 25, "alphanumeric");
      $cf->checkText($_POST[password_r], "repeat password", "min", 6, 25, "alphanumeric");
      if($_POST[password]!=$_POST[password_r])
      {
        $cf->_errors[$cf->_counter]="The repeat password does not match.";
        $cf->_counter++;
      }
    }

    $cf->checkNonNull($_POST[how_heard_id], "How heard");

    $display_errors = $cf->showErrors();
    if(null == $display_errors)
    {
      // No errors - update the database
      $this->insertData();
    }
    else
    {
      // Fill in the form with the previous input, so the user can correct it
      $result['username_m']=$_POST[email_address];
      $result['business_name']=$_POST[business_name];
      $result['last_name']=$_POST[last_name];
      $result['first_name']=$_POST[first_name];
      $result['volunteer_interested']=$_POST[volunteer_interested];
      $result['producer_interested']=$_POST[producer_interested];
      $result['how_heard_id']=$_POST[how_heard_id];
      $result['email_address']=$_POST[email_address];
      $result['email_address_2']=$_POST[email_address_2];
      $result['mobile_phone']=$_POST[mobile_phone];
      $result['membership_type_id']=$_POST[membership_type_id];
      $result['membership_date']=$_POST[membership_date];
      $result['membership_discontinued']=$_POST[membership_discontinued];
      if($_POST[producer_id])
      {
        $p_result['producer_id']=$_POST[producer_id];
        $p_result['donotlist_producer']=$_POST[donotlist_producer];
        $p_result['producttypes']=$_POST[producttypes];
        $p_result['is_supplier']=$_POST[is_supplier];
        $p_result['website']=$_POST[website];
      }

      // Print out the errors and redisplay the form in the correct format
      // depending on whether we already have a member_id
      echo $display_errors;
      if ($_GET[ID])
      {
        $_GET[action] = "edit";
      }
      else
      {
        $_GET[action] = "add";
      }
      include "forms/addmember.form.php";
    }
  }

  function insertData()
  {
    $query=mysql_query("SELECT MD5('".$_POST[password]."')");
    $pass=mysql_fetch_row($query);
    $password=$pass[0];

    $member_id = preg_replace("/[^0-9]/","",$_POST['member_id']);

    if($_POST[producer_id])
    {
      $query_string="
        UPDATE ".$this->producer."
        SET 
          `donotlist_producer`=".$_POST[donotlist_producer].",
          `producttypes`='".$_POST[producttypes]."',
          `is_supplier`='".$_POST[is_supplier]."'
        WHERE `member_id`='".$member_id."';";
      $query=mysql_query($query_string) or die(mysql_error());

      $query_string="
        UPDATE ".TABLE_PRODUCER_REG."
        SET
          `website`='".mysql_real_escape_string($_POST[website])."'
        WHERE `member_id`='".$member_id."';";
      $query=mysql_query($query_string) or die(mysql_error());
    }

     if(!$_POST[password_r])
     {
       $password=$_POST[password];
     }
     
     // If we're creating a new password for an existing member,
     // update it and email it out to them.  (Note: the password
     // will also get updated further below, along with any other changed
     // member information - should sort this.)
     if ($member_id > 0 && $_POST[password_r])
     {
        include("../func/password.php");
        update_password($_POST[password], $_POST[email_address], 
                $_POST[first_name]." ".$_POST[last_name], true);
     }

    if($_POST[no_postal_mail]!=1)
    {
      $_POST[no_postal_mail]=0;
    }
    if($_POST[volunteer_interested]!=1)
    {
      $_POST[volunteer_interested]=0;
    }
    if($_POST[producer_interested]!=1)
    {
      $_POST[producer_interested]=0;
    }
    if($_POST[membership_discontinued]!=1)
    {
      $_POST[membership_discontinued]=0;
    }


    if($member_id>0){
      $query_type = "UPDATE";
    } else {
      $query_type = " INSERT INTO";
    }

    $query_string="".$query_type." ".$this->member." SET
      username_m = '".mysql_real_escape_string($_POST[email_address])."',
      password = '".mysql_real_escape_string($password)."',
      business_name = '".mysql_real_escape_string($_POST[business_name])."',
      last_name = '".mysql_real_escape_string($_POST[last_name])."',
      first_name = '".mysql_real_escape_string($_POST[first_name])."',
      volunteer_interested = '$_POST[volunteer_interested]',
      producer_interested = '$_POST[producer_interested]',
      how_heard_id = '$_POST[how_heard_id]',
      email_address = '".mysql_real_escape_string($_POST[email_address])."',
      email_address_2 = '".mysql_real_escape_string($_POST[email_address_2])."',
      mobile_phone = '".mysql_real_escape_string($_POST[mobile_phone])."',
      membership_type_id = '$_POST[membership_type_id]',
      ".( ($member_id == 0) ? "membership_date = now()," : "")."
      membership_discontinued = '$_POST[membership_discontinued]'";
    if($member_id>0){
      $query_string .= " WHERE member_id = '".$member_id."' ";
    }
    $query=mysql_query($query_string) or die(mysql_error());


    if($_POST[new_producer_id])
    {
      $query_string="SELECT `member_id` FROM ".$this->member." WHERE  `username_m`='$_POST[email_address]';";
      $query=mysql_query($query_string);
      $result=mysql_fetch_row($query);
      $member_id=$result[0];
      $query_string="
        INSERT INTO ".$this->producer."
          (producer_id, member_id, donotlist_producer, producttypes, is_supplier, pub_web)
        values('$_POST[new_producer_id]','$member_id', '$_POST[donotlist_producer]', '$_POST[producttypes]', '$_POST[is_supplier]', '1');";
      $query=mysql_query($query_string) or die(mysql_error());
      $query_string="INSERT INTO ".TABLE_PRODUCER_REG." (producer_id, member_id, business_name, website, date_added)
        values('$_POST[new_producer_id]','$member_id', '$_POST[business_name]', '".mysql_real_escape_string($_POST[website])."', now());";
      $query=mysql_query($query_string) or die(mysql_error());

      // Update the member's authorisation and save producer webpage
      $result = update_member_auth($member_id, $_POST[business_name]);
      $message = save_producer_file($_POST[new_producer_id]);
      
      echo $message;
    }

    if($query && $member_id)
    {
      echo "Member updated!<br><br>";
    }else{
      echo "Member added!<br><br>";
    }

    $this->mainMenu();
    return true;

  }

  function findForm()
  {
    include "forms/findmembers.form.php";
    return;
  }

  function findUsers()
  {
    if(!$_POST[query])
    {
      $query_string = "SELECT * FROM ".$this->member." ORDER BY `last_name`;";
    }
    elseif($_POST[type]=="name")
    {
      //the following code block splits up separate names and searches for and deletes any commas the user mayhave entered
      $names=explode(" ", $_POST[query]);//split search string

      $len=count($names);//how many words?
      for($i=0;$i<$len;$i++)
      {
        $comma=strchr($names[$i], ord(","));//find commas
        if($comma)//delete commas
        {
          $names[$i]=str_replace(","," ",$names[$i]);
          $names[$i]=trim($names[$i]);
        }

      }

      $query_string="SELECT * FROM ".$this->member." WHERE ";

      for($i=0;$i<$len;$i++)
      {
        if($i>0)
        {
          $query_string.=" OR ";
        }

          $query_string.="`last_name` = '".$names[$i]."' OR `first_name` = '".$names[$i]."'";
      }

      $query_string.=" ORDER BY `last_name`;";
      //die("$query_string");
    }
    else
    {
      $query_string="SELECT * FROM ".$this->member." WHERE `".$_POST[type]."` LIKE '%".$_POST[query]."%' ORDER BY `".$_POST[type]."`;";
    }
    
    $query=mysql_query($query_string) or die(mysql_error());
    $rows=@mysql_num_rows($query);
    if($rows>0)
    {
      $this->displayUsers($query, $rows);
      return true;
    }else{
      echo "No members found.  Please search again.<br><br>";
      $this->findForm();
      return false;
    }
  }

  function displayUsers($query, $rows)//entirely a subset of the findUsers()
  {
      echo "<table style='border:.5px black solid;width:80%;' cellspacing='0'>
        <tr bgcolor='#BB0000'>
          <td>Member #</td><td>Name</td><td>Business Name</td><td>Email address</td><td>Action</td>
        </tr>";
      while($result=mysql_fetch_array($query)){
        echo "
        <tr>
          <td>".$result['member_id']."</td><td>".$result['first_name']." ".$result['last_name']."</td><td>".$result['business_name']."</td><td>".$result['username_m']."</td><td><a href='member_interface.php?action=edit&ID=".$result['member_id']."'>Edit</a></td>
        </tr>";
      }
    echo "</table>";
    return true;
  }

  function editUser()
  {
    $query_string="SELECT * FROM ".$this->member." WHERE `member_id`=".$_GET[ID].";";
    $query=mysql_query($query_string);
    $result=mysql_fetch_array($query);

    // Get website from producer registration entry, not member table
    $query_string="
      SELECT
          ".$this->producer.".*,
          ".TABLE_PRODUCER_REG.".website
      FROM
          ".$this->producer.",
          ".TABLE_PRODUCER_REG."
      WHERE
          ".$this->producer.".member_id = ".$_GET[ID]."
      AND ".TABLE_PRODUCER_REG.".member_id = ".$_GET[ID].";";
    
    $query=mysql_query($query_string);
    $p_result=@mysql_fetch_array($query);

    include "forms/addmember.form.php";
    return true;
  }



}


?>
