<?php

// create var btnSearchClicked to check if btnSearch has been clicked
$btnSearchClicked = isset($_POST["btnSearch"]);
$statuses = [];
$car = null;

if ($btnSearchClicked == true) {
	require_once 'db.php';

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);

	if ($conn -> connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$carId = $_POST['patrolCarId'];

	$sql = "SELECT * FROM patrolcar WHERE patrolcar_id = '" . $carId . "'";
	$result = $conn -> query($sql);


	// If the patrolcar_id exists in the db
	if ($row = $result -> fetch_assoc()) {
		$id = $row['patrolcar_id'];
		$statusId = $row['patrolcar_status_id'];
		$car = ["id" => $id, "statusId" => $statusId];
	}

	$sql = "SELECT * FROM patrolcar_status";
	$result = $conn -> query($sql);

	// Use while loop to extract each row into array var statuses
	while ($row = $result -> fetch_assoc()) {
		$id = $row['patrolcar_status_id'];
		$desc = $row['patrolcar_status_desc'];
		$status = ["id" => $id, "desc" => $desc];
		array_push($statuses, $status);
	}

	$conn -> close();

}

// create var btnSearchClicked to check if btnUpdate button has been clicked
$btnUpdateClicked = isset($_POST["btnUpdate"]);

if ($btnUpdateClicked == true) {
	require_once 'db.php';
	$updateSuccess = false;

	$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);

	if ($conn -> connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$newStatusId = $_POST['carStatus'];
	$carId = $_POST['patrolCarId'];

	$sql = "UPDATE patrolcar SET patrolcar_status_id='" . $newStatusId . "'WHERE patrolcar_id= '" . $carId . "'";
	$updateSuccess = $conn -> query($sql);

	if ($updateSuccess === false) {
		echo "Error: " . $sql . "<br>" . $conn -> error;
	}

	// if patrol car is Arrived (4) then capture the time of arrival 
	if ($newStatusId == '4') {
		$sql = "UPDATE dispatch SET time_arrived = NOW() WHERE time_arrived is NULL AND patrolcar_id = '" . $carId . "'";
		$updateSuccess = $conn -> query($sql);
		if ($updateSuccess === false) {
		echo "Error: " . $sql . "<br>" . $conn -> error;
		}

	// if patrol car is Free (3) then capture the time of completion
	} else if ($newStatusId == '3') {

		//First, retrieve the incident ID from dispatch table handled by the patrol car
		$sql = "SELECT incident_id FROM dispatch WHERE time_completed is NULL AND patrolcar_id = '" . $carId . "'";
		$result =$conn -> query($sql);

		$incidentId = 0;
		if ($result -> num_rows > 0) {
			if ($row = $result -> fetch_assoc()) {
				$incidentId = $row['incident_id'];
			}

		}

		// Second, update dispatch table
		$sql = "UPDATE dispatch SET time_completed = NOW() WHERE time_completed is NULL AND patrolcar_id = '" . $carId . "'";
		$updateSuccess = $conn -> query($sql);
		if ($updateSuccess === false) {
		echo "Error: " . $sql . "<br>" . $conn -> error;
		}

		// Third, update the incident table to Free (3)
		$sql = "UPDATE incident SET incident_status_id = '3' WHERE incident_id = '" . $incidentId . "'";
		$updateSuccess = $conn -> query($sql);
		if ($updateSuccess === false) {
		echo "Error: " . $sql . "<br>" . $conn -> error;
		}
	}

	$conn -> close();

	if($updateSuccess === true) {
		header("Location: search.php?message=success&carId=" . $carId . "&newStatusId" . $newStatusId);
	}

}

?>

<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Update Patrol Car Status</title>
        <link href="css/bootstrap.css" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div class="container" style="width: 80%">
            <!-- Use php require_once expression to include header image and navigation bar from nav.php -->
            <?php require_once 'nav.php' ?>
            <!-- Create section container to place web form -->
            <section style="margin-top:20px">
                <!-- Create web form with Caller Name, Contact number, Location of Incident, Type of Incident, Description of Incident input fields-->
                <form action="update.php" method="post">

                	<?php
                		if ($car != null) {

                     		// Row to display Patrol Car Number
                    		echo "<div class='form-group row'>";
                        	echo "<label for='patrolCarId' class='col-lg-4col-form-label'>Patrol Car Number</label>";
                        	echo "<div class='col-lg-8'>";
                        	echo $car['id'];
                        	echo "<input type='hidden' name='patrolCarId' id='patrolCarId' value='".$car['id']."'>";
                        	echo "</div></div>";

                        	// Row to display Patrol Car Status with drop-down input
                    		echo "<div class='form-group row'>";
                        	echo "<label for='carNo' class='col-lg-4col-form-label'>Patrol Car status</label>";
                        	echo "<div class='col-lg-8'>";
                        	echo "<select id='carStatus' class='form-control' name='carStatus'>";
                        	$totalStatus = count($statuses);

                        	for ($i=0; $i<$totalStatus; $i++) {
                        		$status = $statuses[$i];
                        		$selected = "";
                        		if ($status['id'] == $car['statusId']) {
                        			$selected = 'selected="selected"';
                        		}
                        		echo "<option value='" .$status['id'] . "' " . $selected . ">". $status['desc'] . "</option>";
                        		$selected = "";
                        	}
                        	echo "</select>";
                        	echo "</div></div>";


                	}

                ?>

                     <!-- Row to display Update button-->
                    <div class="form-group row">
                        <div class="col-lg-4"></div>
                        <div class="col-lg-8">
                            <input class="btn btn-primary" type="submit" name="btnUpdate" value="Update">
                        </div>
                    </div>

                </form>
            </section>
            <!-- Footer -->
            <footer class="page-footer font-small blue pt-4 footer-copyright text-center py-3">&copy; 2021 Copyright</footer>
        </div>
        <script type="text/javascript" src="js/jquery-3.5.0.min.js"></script>
        <script type="text/javascript" src="js/bootstrap.js"></script>
        <script type="text/javascript" src="js/popper.min.js"></script>
    </body>
</html>