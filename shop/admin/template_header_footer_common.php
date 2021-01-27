<div align="center">
<font face="<?php echo $fontface;?>" size="-1">
<b>[
<?php if ($_SESSION['username_c'] != '') { /* Simple check to see if the member is logged in */ ?>
<a href="index.php">Admin Home</a> |
<a href="orders_list.php?delivery_id=<?php echo $current_delivery_id;?>">Edit Customer Orders</a> |
<a href="adjustments.php">Adjustments</a> |
<a href="orders_prdcr_list.php?delivery_id=<?php echo $current_delivery_id;?>">Wholesale Reports</a> |
<a href="delivery.php">Orders by Location</a> |
<a href="logout.php">Logout</a>
<?php } else { ?>
<a href="<?php echo BASE_URL.PATH;?>index.php">Shopping Home</a> |
<a href="<?php echo HOMEPAGE_URL;?>"><?php echo SITE_NAME;?> Home</a>
<?php } ?>
]</b></font>
</div>