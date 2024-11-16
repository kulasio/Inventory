<?php
require_once 'php_action/db_connect.php';
?>

<?php require_once 'includes/header.php'; ?>

<?php 

$sql = "SELECT * FROM product WHERE status = 1";
$query = $connect->query($sql);
$countProduct = $query->num_rows;

$orderSql = "SELECT * FROM orders WHERE order_status = 1";
$orderQuery = $connect->query($orderSql);
$countOrder = $orderQuery->num_rows;




$totalRevenueSql = "SELECT SUM(grand_total) as total_revenue FROM orders WHERE order_status = 1";
$totalRevenueQuery = $connect->query($totalRevenueSql);
$totalRevenue = $totalRevenueQuery->fetch_assoc()['total_revenue'];


$lowStockSql = "SELECT * FROM product WHERE quantity <= 5 AND status = 1";
$lowStockQuery = $connect->query($lowStockSql);
$countLowStock = $lowStockQuery->num_rows;

$userwisesql = "SELECT users.username , SUM(orders.grand_total) as totalorder FROM orders INNER JOIN users ON orders.user_id = users.user_id WHERE orders.order_status = 1 GROUP BY orders.user_id";
$userwiseQuery = $connect->query($userwisesql);
$userwieseOrder = $userwiseQuery->num_rows;

$fastMovingProductsSql = "SELECT p.product_name, SUM(oi.quantity) as total_quantity 
                         FROM order_item oi 
                         JOIN product p ON oi.product_id = p.product_id 
                         JOIN orders o ON oi.order_id = o.order_id 
                         WHERE o.order_status = 1 
                         GROUP BY p.product_id 
                         ORDER BY total_quantity DESC 
                         LIMIT 5";
$fastMovingProductsQuery = $connect->query($fastMovingProductsSql);

?>






<!-- fullCalendar 2.2.5-->
    <link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.min.css">
    <link rel="stylesheet" href="assests/plugins/fullcalendar/fullcalendar.print.css" media="print">

<style type="text/css">
    body {
        
				background-color: #B23636;
				
    }

    .card {
        background-color: #fff; /* Bootstrap's panel-danger header color */
        color: black; /* Text color for contrast */
        padding: 20px;
        border-radius: 5px;
				text-color
    }

   

    
</style>

<div class="row">
	<?php  if(isset($_SESSION['userId']) && $_SESSION['userId']==1) { ?>
	<div class="col-md-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				
				<a href="product.php" style="text-decoration:none;color:black;">
					Total Product
					<span class="badge pull pull-right"><?php echo $countProduct; ?></span>	
				</a>
				
			</div> <!--/panel-hdeaing-->
		</div> <!--/panel-->
	</div> <!--/col-md-4-->
	
	<div class="col-md-4">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<a href="product.php" style="text-decoration:none;color:black;">
					Low Stock
					<span class="badge pull pull-right"><?php echo $countLowStock; ?></span>	
				</a>
				
			</div> <!--/panel-hdeaing-->
		</div> <!--/panel-->
	</div> <!--/col-md-4-->
	
	
	<?php } ?>  
		<div class="col-md-4">
			<div class="panel panel-info">
			<div class="panel-heading">
				<a href="orders.php?o=manord" style="text-decoration:none;color:black;">
					Total Orders
					<span class="badge pull pull-right"><?php echo $countOrder; ?></span>
				</a>
					
			</div> <!--/panel-hdeaing-->
		</div> <!--/panel-->
		</div> <!--/col-md-4-->

	

		<div class="col-md-4">
    <div class="card">
        <div class="cardHeader">
            <h1><?php echo date('d'); ?></h1>
        </div>
        <div class="cardContainer">
            <p><?php echo date('l') .' '.date('d').', '.date('Y'); ?></p>
        </div>
    </div> 
    <br/>
    <div class="card">
        <div class="cardHeader">
            <h1>$ <?php echo number_format($totalRevenue, 2); ?></h1>
        </div>
        <div class="cardContainer">
            <p>Total Revenue</p>
        </div>
    </div> 
    <br/>
</div>
	
	<?php 
	if(isset($_SESSION['userId']) && $_SESSION['userId']==1) { ?>
	<div class="col-md-8">
		<div class="panel panel-default">
			<div class="panel-heading"> <i class="glyphicon glyphicon-calendar"></i> Dashboard Statistics</div>
			<div class="panel-body">
				<!-- User Wise Order Table -->
				<h4>User Wise Orders</h4>
				<table class="table table-hover table-striped table-bordered" id="userOrderTable">
					<thead>
						<tr>			  			
							<th style="width:40%;">Name</th>
							<th style="width:20%;">Orders ($)</th>
						</tr>
					</thead>
					<tbody>
						<?php while ($orderResult = $userwiseQuery->fetch_assoc()) { ?>
							<tr>
								<td><?php echo $orderResult['username']?></td>
								<td><?php echo $orderResult['totalorder']?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

				<!-- Low Stock Products Table -->
				<h4>Products with Low Stock</h4>
				<table class="table table-hover table-striped table-bordered" id="lowStockTable">
					<thead>
						<tr>                      
							<th style="width:40%;">Product Name</th>
							<th style="width:20%;">Quantity</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						while ($product = $lowStockQuery->fetch_assoc()) { ?>
							<tr>
								<td><?php echo $product['product_name']; ?></td>
								<td><?php echo $product['quantity']; ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

				<!-- Fast Moving Products Chart -->
				<h4>Fast Moving Products Analysis</h4>
				<div id="barchart_values" style="width: 100%; height: 300px;"></div>
			</div>	
		</div>
		
	</div> 
	<?php  } ?>

	
	
</div> <!--/row-->

<!-- fullCalendar 2.2.5 -->
<script src="assests/plugins/moment/moment.min.js"></script>
<script src="assests/plugins/fullcalendar/fullcalendar.min.js"></script>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(drawChart);
    
    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Product', 'Quantity Sold', { role: 'style' }],
            <?php
            $colors = ['#3366CC', '#DC3912', '#FF9900', '#109618', '#990099']; // Define colors for bars
            $index = 0;
            while ($product = $fastMovingProductsQuery->fetch_assoc()) {
                echo "['" . addslashes($product['product_name']) . "', " . $product['total_quantity'] . ", '" . $colors[$index % 5] . "'],";
                $index++;
            }
            ?>
        ]);

        var options = {
            title: 'Most Ordered Products',
            legend: { position: 'none' },
            hAxis: {
                title: 'Products'
            },
            vAxis: {
                title: 'Quantity Sold',
                minValue: 0
            }
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('barchart_values'));
        chart.draw(data, options);
    }
</script>

<script type="text/javascript">
	$(function () {
			// top bar active
	$('#navDashboard').addClass('active');

      //Date for the calendar events (dummy data)
      var date = new Date();
      var d = date.getDate(),
      m = date.getMonth(),
      y = date.getFullYear();

      $('#calendar').fullCalendar({
        header: {
          left: '',
          center: 'title'
        },
        buttonText: {
          today: 'today',
          month: 'month'          
        }        
      });


    });
</script>

<?php $connect->close(); ?>
<?php require_once 'includes/footer.php'; ?>