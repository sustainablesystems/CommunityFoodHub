<?php
$user_type = 'valid_c';
include_once ("config_foodcoop.php");
session_start();
validate_user();

function getMembers($producer)
  {
    $wherestatement = ' WHERE m.membership_discontinued = 0 ';
    if ( $producer > 0 )
      {
        $wherestatement .= '
          AND
            auth_type
          LIKE
            "%producer%" ';
      }
    $sql = mysql_query('
          SELECT
            m.*,
            how_heard_name
          FROM
            '.TABLE_MEMBER.' m
          LEFT JOIN
            '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.member_id = m.member_id
          LEFT JOIN
            '.TABLE_HOW_HEARD.' ON '.TABLE_HOW_HEARD.'.how_heard_id = m.how_heard_id
          '.$wherestatement.'
          ORDER BY
            last_name');
    while ( $row = mysql_fetch_array($sql) )
      {
        $members[] = $row;
      }
    return $members;
  }
if ( $_REQUEST['p'] == 1 )
  {
    $producers = 1;
  }
else
  {
    $producers = 0;
  }
$members = getMembers($producers);
if($_GET['export'] == "csv")
  {
    // Send output to spreadsheet
    $export = "ID,Last name,First name,Email,Phone,Balance,Joined,Type,Pending?,Volunteer?,Supply?,How heard\n";
    foreach( $members as $key=>$row )
      {
        $search = array('/\n/', '/\r/', '/"/', '/(.*),(.*)/');
        $replace = array(' ', ' ', '"""', '"\1,\2"');
        $export .=  preg_replace ($search, $replace, stripslashes($row['member_id'])).','.
                    preg_replace ($search, $replace, stripslashes($row['last_name'])).','.
                    preg_replace ($search, $replace, stripslashes($row['first_name'])).','.
                    preg_replace ($search, $replace, stripslashes($row['email_address'])).','.
                    preg_replace ($search, $replace, stripslashes($row['mobile_phone'])).','.
                    preg_replace ($search, $replace, stripslashes($row['curr_balance'])).','.
                    preg_replace ($search, $replace, stripslashes($row['membership_date'])).','.
                    preg_replace ($search, $replace, stripslashes($row['auth_type'])).','.
                    preg_replace ($search, $replace, stripslashes($row['pending'])).','.
                    preg_replace ($search, $replace, stripslashes($row['volunteer_interested'])).','.
                    preg_replace ($search, $replace, stripslashes($row['producer_interested'])).','.
                    preg_replace ($search, $replace, stripslashes($row['how_heard_name']))."\n";
      }
    header("Content-type: application/octet-stream"); 
    header("Content-Disposition: attachment; filename=foodcoop-".date('Y-m-d').".csv"); 
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
    echo $export;
//    exit;
  }
else
  {
    // Send output to web page
    include_once("../func/table_sort.php");
    include_once("template_hdr.php");

    $members_count = count($members);

    echo '<div align="center">';
    if ( $producers )
      {
        echo '<h2>'.$members_count.' Producer Members (Click to <a href="'.$_SERVER['PHP_SELF'].'?p=0">show all Members</a>)</h2>';
      }
    else
      {
        echo '<h2>'.$members_count.' Members (Click to <a href="'.$_SERVER['PHP_SELF'].'?p=1">show only Producers</a>)</h2>';
      }
    echo '<p><b>Tip:</b> click column headings to change the sorting of table, for instance click "Balance" to sort the table by balance.<br>
             <b>Note:</b> discontinued members are not shown in the table below.</p>';
    echo '
      <table id="member_table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Balance</th>
            <th>Joined</th>
            <th>Type</th>
            <th>Pending?</th>
            <th>Volunteer?</th>
            <th>Supply?</th>
            <th>How heard</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>';
    foreach( $members as $key=>$row )
      {
        echo '
          <tr>
            <td>'.$row['member_id'].'</td>
            <td>'.stripslashes($row['last_name']).', '.stripslashes($row['first_name']).'</td>
            <td>'.$row['email_address'].'</td>
            <td>'.$row['mobile_phone'].'</td>
            <td>'.CURSYM.$row['curr_balance'].'</td>
            <td>'.$row['membership_date'].'</td>
            <td>'.str_replace (',', '<br>', stripslashes($row['auth_type'])).'</td>
            <td>'.($row['pending'] == 0 ? 'No' : 'Yes').'</td>
            <td>'.($row['volunteer_interested'] == 0 ? 'No' : 'Yes').'</td>
            <td>'.($row['producer_interested'] == 0 ? 'No' : 'Yes').'</td>
            <td>'.stripslashes($row['how_heard_name']).'</td>
            <td><a href="member_interface.php?action=edit&ID='.$row['member_id'].'">Edit member</a>
              <br><a href="members_invoices.php?member_id='.$row['member_id'].'">View invoices</a></td>
          </tr>';
      }
    echo '
        </tbody>
      </table>
      <script type="text/javascript">
        window.addEvent( \'domready\', function(){
          new SortingTable(
            \'member_table\',
            { last_row: false }
          );
        })</script>
      <br/>
      <form action="'.$_SERVER['PHP_SELF'].'?p='.$_REQUEST['p'].'&export=csv" method="POST">
      <input type="submit" name="submit" value="Export Table to Spreadsheet (CSV file)">
      </form>
      </div>';
    include("template_footer.php");
  }

?>