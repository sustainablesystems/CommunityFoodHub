
<?php
class formValidator
{
  
  //internal variables
  var $_counter;
  var $_errors;
  
  //constructor  
  function formValidator()
  {
    $this->_counter=0;
    $this->_errors=array();
  }
  
  function checkText()
  {
    $arguments=func_get_args();
    $value = (null == $arguments[0]) ? "" : trim($arguments[0]);
    $field_name=$arguments[1];
    $range=$arguments[2];
    $minlength=$arguments[3];
    $maxlength=$arguments[4];
    $asciirange=$arguments[5];

    if($value=="")
    {
      if($field_name!=NULL)
      {
        $this->_errors[$this->_counter]="Please fill in ".$field_name.".";
        $this->_counter++;
      }
      return false;
        
    }elseif($range!=NULL){
      $this->_checkNum($value, $field_name, $range, $minlength, $maxlength);
      
      
    }
    if($asciirange!=NULL){
      
      $len=strlen($value);

      for($i = 0; $i < $len; $i++)//cycle through it all and look for the at and . signs.
      {
        
        switch($asciirange)
        {
          case "numeric":
            if(ord($value[$i])<48 || ord($value[$i])>57)
            {
              $this->_errors[$this->_counter]="$field_name can only contain numbers.";
              $this->_counter++;
              return false;
            }
            break;
          case "alphanumeric":
              if(!((ord($value[$i])>=48 && ord($value[$i])<=57) || (ord($value[$i])>=64 && ord($value[$i])<=90) || (ord($value[$i])>=97 && ord($value[$i])<=122) || ord($value[$i])==95 || ord($value[$i])==46))
            {
              $this->_errors[$this->_counter]="$field_name can only contain alphnumeric character (and @ for e-mail addresses.";
              $this->_counter++;
              return false;
            }
            break;
        }//close switch  
      }//close for  
    }//close if
    return true;
  }//close function
  
  //checks to make sure that an e-mail address is a] there and b] follows the name@domain.suffix
  function validateEmail()
  {
    $is_email_ok = false;
    $args=func_get_args();
    $value=$args[0];
    $field_name=$args[1];

    if (!filter_var($value, FILTER_VALIDATE_EMAIL))
    {
      $this->_errors[$this->_counter] = "You must enter a valid ".$field_name.".";
      $this->_counter++;
    }
    else // Email address validates
    {
      $is_email_ok = true;
    }

    return $is_email_ok;
  }    
  
  function validateZip($value, $return_info)
  {
    if(!$this->checkText($value))
    {
      $this->_errors[$this->_counter]="Please enter a postcode.";
      $this->_counter++;
      return false;
    }
    
    $conn = new connection;
    $conn->makeConnection(/*local zip dbase*/);
    $query = "SELECT * FROM geo_refs WHERE `zip` = ".$value.";";
    
    $match = mysql_query($query, $conn->connection);
    $result = mysql_fetch_row($match);
    
    mysql_close();
      
    if($result==NULL)
    {
      
      $this->_errors[$this->_counter] = "You must enter a VALID postcode.";
      $this->_counter++;
      return false;
    }else{
      if($return_info==true)
      {
        return $result;
      }else{
        return true;
      }
    }
  
  }

  function checkNonNull($value, $fieldname)
  {
    $is_value_non_null = true;

    if($value == 0)
    {
      $this->_errors[$this->_counter]= 'Please enter a value for "'.$fieldname.'".';
      $this->_counter++;

      $is_value_non_null = false;
    }

    return $is_value_non_null;
  }
  
  //Takes the string $actual, checks its length and then depending on the value in $minmax, checks to see whether it is too low, high or out of a range.  Works.
  function _checkNum($actual, $fieldname, $minmax, $min, $max)
  {
    $len=strlen($actual);
    
    switch($minmax)
    {
      case "min":
        if($len<$min)
        {
          $this->_errors[$this->_counter]= $fieldname." is too short.";
          $this->_counter++;
          
          return false;
        }else{
          return true;
        }
        break;
        
      case "max":
        if($len>$max)
        {
          $this->_errors[$this->_counter]= $fieldname." is too long.";
          $this->_counter++;
          return false;
        }else{
          return true;
        }
        break;
      case "inside":
      
        if($len<$min || $len>$max)
        {
          $this->_errors[$this->_counter]= $fieldname." is outside of value range.";
          $this->_counter++;
          return false;
        }
        break;
      }
    }
    
  
  function showErrors()
  {
    $display_errors = null;

    if($this->_counter>0)
    {
      $display_errors .= "<div style='color:#ff0000;font-size:14px;'>";
      $display_errors .= "<b>Please correct form errors:</b><br><br>";
      foreach($this->_errors as $error)
      {
        $display_errors .= $error."<br>";
      }
      $display_errors .= "</div>";
    }

    return $display_errors;
  }
  
}

?>
