<html>
    	<head>
        <title>DNS Query Results</title>
    	</head>
    	<body>
        <center>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="post">
		Domain: <input type="text" name="domainName"><br>
		<input type="submit" name="searchA" value="SearchA"><input type="submit" name="searchAAAA" value="SearchAAAA"><input type="submit" name="searchCNAME" value="SearchCNAME"><input type="submit" name="searchMX" value="Search MX"><input type="submit" name="searchNS" value="Search NS"><input type="submit" name="searchInverse" value="Inverse Query"><input type="checkbox" name="iterative" value="iterative">Iterative Query<br>
	</form>
<?php
$domainName=$_POST["domainName"];
if(!$domainName){exit(0);}
	if (isset($_POST["searchA"])){
		$queryType = "A";unset($_POST["searchA"]);
	}
	else if(isset($_POST["searchAAAA"])){
		$queryType = "AAAA";unset($_POST["searchAAAA"]);
	}
	else if(isset($_POST["searchCNAME"])){
		$queryType = "CNAME";unset($_POST["searchCNAME"]);
	}
	else if(isset($_POST["searchMX"])){
		$queryType = "MX";unset($_POST["searchMX"]);
	}
	else if(isset($_POST["searchNS"])){
		$queryType = "NS";unset($_POST["searchNS"]);
	}
	else if(isset($_POST["searchInverse"])){
		$queryType = "reverse";unset($_POST["searchInverse"]);
	}
	else{ exit(0);}
if(isset($_POST["iterative"])) {
	$result = shell_exec('iterative '.$domainName.' '.$queryType);
	unset($_POST["iterative"]);
	$result = explode("\n", $result);?>
	<div>
		<ul style="list-style-type:none"><?php
		foreach($result as $row) {?>
			<li> <?php echo $row?></li><?php
		}?>
		</ul>
	</div><?php
	exit(0);
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
if ($queryType == "reverse") {
	$sql = "SELECT * FROM dns where result= '".$domainName."'";
}
else {
	$sql = "SELECT * FROM dns where name= '".$domainName."' and type='".$queryType."'";
}
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if (empty($row)) {?>
	<p> Not found in local database..... searching.... </p>
	<?php
	$result = shell_exec('resolver '.$domainName.' '.$queryType);
	$result = json_decode($result, true);
	$answer = $result['answer'];
	$authority = $result['authority'];
	$additional = $result['additional'];
	$query_details = $result['details'];
	if (count($answer)==0) {?>
		<img src="animation2.gif" alt="Invalid domain name" width=49%>
		<p> No results for this query </p> <?php
	}
	else {?>
		<img src="animation1.gif" alt="animation" width=49%>
		<table border="1" style="width:49%">
		<caption> <b>Fetched Results</b> </caption>
	        <thead>
	            	<tr>
	                <td>Name</td>
	                <td>Type</td>
			<td>Result</td>
	            	</tr>
	        </thead>
	        <tbody>
		<?php
			$i = 0;
        		for($i=0; $i < count($answer); $i++) {
            			$name = $answer[$i]['name'];
            			$type = $answer[$i]['type'];
            			$res = $answer[$i]['result'];
				if($queryType != "reverse"){
				$conn->query("INSERT INTO dns values('".$name."', '".$type."', '".$res."', 1)");}?>
				<tr>
                    			<td><?php echo $name?></td>
                    			<td><?php echo $type?></td>
		    			<td><?php echo $res?></td>
                		</tr><?php
			}
	}?>
		</tbody>
		</table><br><br>
		<table border="1" style="width:49%">
		<caption> <b>Authorities</b> </caption>
	        <thead>
	            	<tr>
	                <td>Name</td>
			<td>IP</td>
	            	</tr>
	        </thead>
	        <tbody>
		<?php
			$i = 0;
        		for($i=0; $i < count($authority); $i++) {
            			$name = $authority[$i]['result'];
				$j=0;
				for($j=0; $j < count($additional); $j++) {
					if($additional[$j]['name'] == $authority[$i]['result']) {
            					$ip = $additional[$j]['result'];
						break;
					}
				}?>
				<tr>
                    			<td><?php echo $name?></td>
                    			<td><?php echo $ip?></td>
                		</tr><?php
			}?>
		</tbody>
		</table><?php
}
else {?>
	<p> Found in local database </p>
	<img src="animation.gif" alt="animation" width=49%>
	<table border="1" style="width:50%">
	<thead>
		<tr>
			<td>Name</td>
			<td>Type</td>
			<td>Result</td>
		</tr>
	</thead>
	<tbody>
		<?php
	while($row){?>
                <tr>
                    <td><?php echo $row['name']?></td>
                    <td><?php echo $row['type']?></td>
		    <td><?php echo $row['result']?></td>
                </tr>

            	<?php
			$conn->query("UPDATE dns SET uses = uses + 1 WHERE result = '".$row['result']."'");
			if($row['type']=="CNAME"){
			$result1 = $conn->query("SELECT * from dns where name='".$row['result']."'");
			$row1 = $result1->fetch_assoc();
			while($row1){?>
					<tr>
                    				<td><?php echo $row1['name']?></td>
                    				<td><?php echo $row1['type']?></td>
		    				<td><?php echo $row1['result']?></td>
                			</tr>
				<?php
					$conn->query("UPDATE dns SET uses = uses + 1 WHERE result = '".$row1['result']."'");
					$row1 = $result1->fetch_assoc();
				}
			}
		$row = $result->fetch_assoc();
	}?>
	</tbody>
	</table><?php
}
?>
</center>
</body>
</html>
