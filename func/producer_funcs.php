<?php

include_once ("config_foodcoop.php");

function update_member_auth($member_id, $business_name)
{
  global $connection;
  
  $query_string = '
    UPDATE
      '.TABLE_MEMBER.'
    SET
      auth_type = CONCAT_WS(",", auth_type, "producer"),
      business_name = "'.mysql_escape_string($business_name).'"
    WHERE
      member_id = "'.mysql_escape_string($member_id).'"';

  $result = mysql_query($query_string) or die(mysql_error());

  return $result;
}

function save_producer_file($producer_id)
{
  $file = fopen (FILE_PATH.PATH.'producers/'.strtolower($producer_id).".php", "w");

  if($file)
  {
    $filetext = "<?php include('../template_prdcr.php'); ?>";
    fwrite ($file, $filetext);
    fclose($file);

    $message = '<p class="error_message">Producer information has been accepted.</p>';
  }
  else
  {
    $message = '<p class="error_message">Producer information was accepted but there
      was an error creating the producer file</p>';
  }
      
  return $message;
}

?>
