<div align="center">
<table width="70%">
  <tr><td align="left">

<?php 
if($_GET[action]=="edit")
{
  $action="member_interface.php?action=checkMemberForm&ID=$_GET[ID]".($_GET[password] ? "&password=edit" : "");
  $title="Edit Member/Producer Information";
}else{
  $action="member_interface.php?action=checkMemberForm";
  $title="Add A New Member/Producer";
}
echo "<form action='$action' method='post' name='addMember'>";
echo '<h3 align="center">'.$title.'</h3>';
?>
<p align="center"><font color=#3333FF><b>*</b></font> means it is a required field.</p>
<table width="800" border="1" cellpadding="2" cellspacing="2" bordercolor="#333333" align="center">
  <tr bgcolor="#BB0000"> 
    <td colspan="3"><font face="arial"><b>Personal Info</b></font></td>
  </tr>
  <tr> 
    <td width="50" align="center"><font color="#3333FF"><b>*</b></font></td>
    <td width="150" bgcolor="#CCCCCC">First name</td>
    <td width="350" bgcolor="#CCCCCC"> <input name="first_name" type="text" id="first_name4" size="20" maxlength="25" <?php echo "value='".$result['first_name']."'"; ?> ></td>
  </tr>
  <tr> 
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td bgcolor="#CCCCCC">Last name </td>
    <td bgcolor="#CCCCCC"> <input name="last_name" type="text" id="last_name4" size="20" maxlength="25" <?php echo "value='".$result['last_name']."'"; ?> ></td>
  </tr>
  <tr> 
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td bgcolor="#CCCCCC">Contact phone </td>
    <td bgcolor="#CCCCCC"> <input name="mobile_phone" type="text" id="mobile_phone3" size="15" maxlength="20" <?php echo "value='".$result['mobile_phone']."'"; ?> ></td>
  </tr>  
  <tr> 
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td bgcolor="#CCCCCC">Email address</td>
    <td bgcolor="#CCCCCC"> <input name="email_address" type="text" id="email_address3" size="30" maxlength="100" <?php echo "value='".$result['email_address']."'"; ?> ></td>
  </tr>
  <tr> 
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td bgcolor="#CCCCCC">Repeat email address</td>
    <td bgcolor="#CCCCCC"> <input name="email_address_2" type="text" id="email_address_24" size="30" maxlength="100" <?php echo "value='".$result['email_address_2']."'"; ?>  ></td>
  </tr>
  
  <tr bgcolor="#BB0000"> 
    <td colspan="3"><font face="arial"><b>Password</b></font></td>
  </tr>
  <tr> 
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <?php
   if($_GET[ID] && !$_GET[password])
   {
     echo "<td bgcolor='#CCCCCC' colspan='2'>Password stored. <a href='member_interface.php?action=edit&ID=$_GET[ID]&password=edit#password'>Click here</a> to set a new password. <input type='hidden' name='password' value='".$result['password']."' /></td>";
  }else{
    echo "<td bgcolor='#CCCCCC'>Password</td>";
    echo "<td bgcolor='#CCCCCC'><a name='password'/><input name='password' type='password' id='password3' size='15' maxlength='25'> (min 6 characters, no spaces)</td>";
  } ?>
  </tr>
  <?php 
  if(!$_GET[ID] || $_GET[password])
  {
    echo "<tr><td align='center'><font color='#3333FF'><b>*</b></font></td><td bgcolor='#CCCCCC'>Repeat password</td>
      <td bgcolor='#CCCCCC'><input name='password_r' type='password' id='password_r4' size='15' maxlength='25'></td></tr>";
   }?>
  <tr bgcolor="#BB0000"> 
    <td colspan="3"><font face="arial"><b>Account Info</b></font></td>
  </tr>
  <tr>
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td bgcolor="#CCCCCC">Membership type</td>
    <td bgcolor="#CCCCCC">
      <input name="membership_type_id" type="text" id="membership_type_id3" size="10" maxlength="10" readonly="readonly"
        <?php
          // TODO: Set 1 as the default membership type when adding members.  Do we want multiple types?
          // readonly, as this is set by edit_member_types.php (but could enable setting it here too).
          echo ($_GET[ID] ? "value='".$result['membership_type_id']."'" : "value='1'" );
        ?>
      />
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2" bgcolor="#CCCCCC"> <input name="volunteer_interested" type="checkbox" value="1"
      <?php if($result['volunteer_interested']==1){ echo "checked";} ?> />
      Interested in volunteering</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2" bgcolor="#CCCCCC"> <input name="producer_interested" type="checkbox" value="1"
      <?php if($result['producer_interested']==1){ echo "checked";} ?> />
      Interested in supplying</td>
  </tr>
  <tr>
    <td align="center"><font color="#3333FF"><b>*</b></font></td>
    <td colspan="2" bgcolor="#CCCCCC">
      How heard 
      <select name="how_heard_id">
        <?php
          // Get how-heard select options
          include_once('../func/how_heard.php');
          $how_heard_options = get_how_heard_options($result['how_heard_id']);
          echo $how_heard_options;
        ?>
      </select>
    </td>
  </tr>

  <?php 
  if($_GET[ID])
  {
  echo "<tr><td>&nbsp;</td><td colspan='2' bgcolor='#CCCCCC'><input name='membership_discontinued' type='checkbox' id='membership_discontinued' value='1' ";
     if($result['membership_discontinued']==1)
   {
      echo "checked";
    } 
   echo " /> Membership discontinued (this will stop the member logging in, and remove him or her from the membership list)</td></tr>";
  }
   ?>
   <tr bgcolor="#BB0000"> 
    <td colspan="3"><font face="arial"><b>Producer Information (optional)</b></font></td>
  </tr>
  <tr> 
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Producer ID<br>(5 letters)</td>
    <td bgcolor="#CCCCCC">
  <?php 
  if(!$p_result['producer_id'])
  {
    echo "<input name='new_producer_id' type='text' size='8' maxlength='5'>";
  }else{
    echo $p_result['producer_id']."<input name='producer_id' type='hidden' value='".$p_result['producer_id']."' />";
  }?>
  </td>
  </tr>
  <tr> 
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Business name </td>
    <td bgcolor="#CCCCCC"> <input name="business_name" type="text" id="business_name4" size="30" maxlength="50" <?php echo "value='".$result['business_name']."'"; ?> ></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Types of product </td>
    <td bgcolor="#CCCCCC"> <input name="producttypes" type="text" size="80" maxlength="120" <?php echo "value='".$p_result['producttypes']."'"; ?> ></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Supplier status</td>
    <td bgcolor="#CCCCCC"> <input name="is_supplier" type="checkbox" value="1" <?php if($p_result['is_supplier']==1){ echo "checked";} ?> />
    Tick this box if this producer is actually a supplier (a middleman).
    Suppliers appear more transparently to customers.
    <br><br><i>Tip: a supplier account may also be used in place of several producer
    accounts if only one person - a "producer coordinator" - updates products
    for the entire website.</i>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Active status</td>
    <td bgcolor="#CCCCCC"> 
      <input name="donotlist_producer" type="radio" value="0" checked <?php if($p_result['donotlist_producer']!=1){ echo "checked"; } ?> />
      List <br>
      <input type="radio" name="donotlist_producer" value="1" <?php if($p_result['donotlist_producer']==1){ echo "checked"; } ?> >
      Do not list (this will stop the member selling products)</td>
  </tr>
  <tr> 
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Website </td>
    <td bgcolor="#CCCCCC">http://
<input name="website" type="text" id="website3" size="30" <?php echo "value='".$p_result['website']."'"; ?> ></td>
  </tr>
</table>
<p align="center">
  <input type="hidden" name="member_id" <?php echo "value='".$_GET[ID]."'"; ?> >
  <input type="submit" name="Submit" <?php echo ($_GET[ID] ? 'value="Update Entry"' : 'value="Add Member"'); ?> />
</p>
</form>

  </td></tr>
</table>
<br>

</div>