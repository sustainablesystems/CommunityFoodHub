<?php

 function prod_origin_get($origin_id)
 {
     global $connection;

     $sql = '
         SELECT *
         FROM '.TABLE_ORIGIN.' 
         WHERE origin_id = "'.$origin_id.'"';
     $result = @mysql_query($sql,$connection) or die(mysql_error());
     while ($row = mysql_fetch_array($result))
     {
         $country = $row['country'];
         $uk_county = $row['uk_county'];

         if ( $country && $uk_county )
         {
             $origin = $uk_county . ', ' .$country;
         }
         else
         {
             $origin = $country ? $country : $uk_county;
         }
     }
     return $origin;
 }

 function prod_brand_get($brand_id)
 {
     global $connection;

     $sql = '
         SELECT *
         FROM '.TABLE_BRAND.'
         WHERE brand_id = "'.$brand_id.'"';
     $result = @mysql_query($sql,$connection) or die(mysql_error());
     while ($row = mysql_fetch_array($result))
     {
         $brand = $row['brand_name'];
     }
     return $brand;
 }
?>
