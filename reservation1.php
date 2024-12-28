<?php 
 
	/*
	* Created by Belal Khan
	* website: www.simplifiedcoding.net 
	* Retrieve Data From MySQL Database in Android
	*/
	
	require "conn.php";

	//Checking if any error occured while connecting
	if (mysqli_connect_errno()) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
		die();
	}
	
	//creating a query
	$stmt = $conn->prepare("SELECT id, id_client, data, time, free, price, type_service, brand, model, VRC, year FROM reservation ORDER BY time ASC;");
	
	//executing the query 
	$stmt->execute();
	
	//binding results to the query 
	$stmt->bind_result($id, $id_client, $data, $time, $free, $price, $type_service, $brand, $model, $VRC, $year);
	
	$products = array(); 
	
	//traversing through all the result 
	while($stmt->fetch()){
		$temp = array();
		$temp['id'] = $id; 
		$temp['id_client'] = $id_client;
		$temp['data'] = $data; 
		$temp['time'] = $time; 

		$temp['free'] = $free; 
		$temp['price'] = $price; 
		$temp['type_service'] = $type_service; 

        $temp['brand'] = $brand; 
		$temp['model'] = $model; 
		$temp['VRC'] = $VRC; 
		$temp['year'] = $year; 
		
		array_push($products, $temp);
	}
	
	//displaying the result in json format 
	echo json_encode($products);