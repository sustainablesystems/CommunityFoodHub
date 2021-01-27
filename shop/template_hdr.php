<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo SITE_NAME;?> - Shop</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Language" content="en-uk">
<?php if (FAVICON != '') { echo '<link rel="shortcut icon" href="'.FAVICON.'" type="image/x-icon" />';} ?>
<style type="text/css">
body, p, td {
  font-family: Verdana;
  font-size: 9pt;
  color: #000000;
  padding-right: 5px;
  }

h1 {
  font-family: Arial;
  text-align:center;
  }

em {
  color: #758954;
  }
table.proddata {
  font-size: 1.2em;
  border: 1px solid black;
  empty-cells:show;
  }
tr.d0 td {
  background-color: #eeeeee;
  color: black;
  border-top:1px solid black;
  }
tr.d00 td {
  background-color: #eeeeee;
  color: black;
  }
tr.d1 td {
  background-color: #f8f8f8;
  color: black;
  border-top:1px solid black;
  }
td.b {
  border-left: 2px solid #dddddd;
  border-right: 2px solid #dddddd;
  }
td.memform {
  border: 1px solid #ccc;
  }
  
/* ------- Calendar Styles TODO: Don't want to include with every page!'-------- */
.calcell, .today, .calkey {
 font-size: 0.8em;
}

.calcellO, .todayO { /* Cycle-ordering day */
 background: #b2c46c;
 font-size: 0.8em;
}
.calcellD, .todayD { /* Delivery-day */
 background: #505e40;
 color: #f0f4d3;
 font-size: 0.8em;
}
.calcellR, .calcellR a { /* Red-letter day */
 background: #7B0005;
 color: #ffeedd;
 font-size: 0.8em;
}
td.today, td.todayO, td.todayD, td.todayR {
 border: 1px solid #aa0000;
}
table.calendar {
 border: 1px solid #d0d4b3;
 background:#f6fad9;
}
.calendar-day {
 font-size: 0.8em;
}

.tab {
 margin-left:1em;
}

</style>

</head>

<body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#FFFFFF;">
  <tr>
    <td align="center">
      <div>
        <?php if (SHOW_HEADER_LOGO === true) { ?>
        <a href="<?php echo BASE_URL.PATH;?>">
        <img src="<?php echo DIR_GRAPHICS; ?>logo.gif" border="0" alt="Food <?php echo ORGANIZATION_TYPE; ?>" align="center"></a>
        <br />
        <?php }
        if (SHOW_HEADER_SITENAME === true) { ?>
        <h2><?php echo SITE_NAME;?></h2>
        <?php } ?>
      </div>
    </td>
  </tr>
  <tr>
    <td align="center">
      <?php
      include(FUNC_FILE_PATH.'header_footer_common.php');
      echo $links;
      ?>
    </td>
  </tr>
</table>
