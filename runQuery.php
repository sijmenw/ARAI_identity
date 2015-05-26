<!DOCTYPE HTML>
<html lan="en">
	<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	</head>
	
	<body>
		<?php
			function parseResponseAmount($str) {				
				$i = 0;
				
				$startAmountParse = false;
				
				$amount = "";
				
				$strlen = strlen($str);
				
				while ($i++ < $strlen) {
					$char = $str[$i];
					if ($char == ':') {
						$startAmountParse = true;
						continue;
					} else if ($char == ',') {
						$startAmountParse = false;
						break;
					}
					if ($startAmountParse == true) {
						$amount .= $char;
					}
				}
				return intval($amount);
			}
		
			function parseResponseResults($str) {
				$arr = array();
				
				$startArrayParse = false;
				
				$element = "";
				
				$strlen = strlen($str);
				
				for ($i = 0; $i < $strlen; $i++) {
					$char = $str[$i];
					if ($char == '[') {
						$startArrayParse = true;
						continue;
					} else if ($char == ']') {
						array_push($arr, $element);
						$startArrayParse = false;
					}
					if ($startArrayParse == true) {
						// Ignore these characters
						if ($char == '"') {
							continue;
						}
						// Go to next value
						if ($char == ',') {
							array_push($arr, $element);
							$element = "";
							continue;
						}
						$element .= $char;
					}
				}
				return $arr;
			}
			
			function printArray($arr) {
				if (!is_array($arr)) {
					return "";
				}
				$arr_str = "";
				recursiveArrayToString($arr, $arr_str);
				return $arr_str;
			}
			
			function recursiveArrayToString($arr, &$str) {
				$str .= "<ul>";
				foreach($arr as $key => $value) {
					$str .= "<li>" . $key;
					if (is_array($value)) {
						recursiveArrayToString($value, $str);
					} else {
						$str .= ": " . $value;
					}
					$str .= "</li>";
				}
				$str .= "</ul>";
			}
		?>
	
		<?php
			$person = 'Frank_van_Harmelen';
			$lodDatasets = 'http://index.lodlaundromat.org/r2d/';
			$lodEndpoint = 'http://ldf.lodlaundromat.org/';
			$db_prefix = 'http://dbpedia.org/resource/';
			$owlSameAs = 'http://www.w3.org/2002/07/owl#sameAs';
			$person_uri = $db_prefix . $person;
			$limit = 'limit=1000';
			
			$endpoints_query = $lodDatasets . urlencode($person_uri) . "?" . $limit;
			
			echo "<div style=\"height:40px;\">First Query: " . $endpoints_query . "</div>";
			
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $endpoints_query,
				CURLOPT_USERAGENT => 'Sample cURL Request'
			));
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);
			
			// Get the amount of results from the query
			$endpointCount = parseResponseAmount($resp);
			
			// Parse the response into an array
			$resp_arr = parseResponseResults($resp);
			
			// Place all endpoints in a list
			$endpoint_list = '<ol type="1">';
			foreach ($resp_arr as $value) {
				$endpoint_list .= '<li>' . $value . '</li>';
			}
			$endpoint_list .= '</ol>';
			
			// Print out endpoints
			echo "<div style=\"width:350px;height:300px;overflow:scroll;\">Endpoints (n=" . $endpointCount . "):\r\n" . $endpoint_list . "</div>";
			
			// Loop over endpoints to find owl:sameAs statements...
			$objects_arr = array(array());
			$subject_arr = array(array());
			$sameAs_arr = array(array());
			foreach ($resp_arr as $endpoint) {
				$sameAsObjectsQuery = $lodEndpoint . $endpoint . '?subject=' . urlencode($person_uri) . '&predicate=' . urlencode($owlSameAs) . '&object=';
				$sameAsSubjectsQuery = $lodEndpoint . $endpoint . '?subject=' . '&predicate=' . urlencode($owlSameAs) . '&object=' . urlencode($person_uri);
				
				//echo "<div style=\"height:40px;\">Second Query: " . $sameAsObjectsQuery . "</div>";
				
				// Get cURL resource
				$curl = curl_init();
				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $sameAsObjectsQuery,
					CURLOPT_USERAGENT => 'Sample cURL Request',
					CURLOPT_HTTPHEADER => array('Accept: application/json')
				));
				// Send the request & save response
				$sameAsObjects = curl_exec($curl);
				// Decode json response into array
				$sameAsObjects_arr = json_decode($sameAsObjects, true);
				
				$sameAsObjects_str = "<div style=\"height:300px;overflow:scroll;\">";
				$sameAsObjects_str .= printArray($sameAsObjects_arr);
				$sameAsObjects_str .= "</div>";
				
				//echo $sameAsObjects_str;
				
				//echo "<div style=\"height:40px;\">Third Query: " . $sameAsSubjectsQuery . "</div>";
				
				// Rest the query for the subjects
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $sameAsSubjectsQuery,
					CURLOPT_USERAGENT => 'Sample cURL Request',
					CURLOPT_HTTPHEADER => array('Accept: application/json')
				));
				// Send the new request & save response
				$sameAsSubjects = curl_exec($curl);
				// Decode json response into array
				$sameAsSubjects_arr = json_decode($sameAsSubjects, true);
				
				$sameAsSubjects_str = "<div style=\"height:300px;overflow:scroll;\">";
				$sameAsSubjects_str .= printArray($sameAsSubjects_arr);
				$sameAsSubjects_str .= "</div>";
				
				//echo $sameAsSubjects_str;
				
				// Insert sameAs (subjects) URI's into array
				foreach($sameAsObjects_arr[0] as $key => $value) {
					if (/*key == object*/) {
						$sameAs_arr[$endpoint][] = $value;
					}
				}
				// Insert sameAs (objects) URI's into array
				foreach($sameAsSubjects_arr[0] as $key => $value) {
					if (/*key == subject*/) {
						$sameAs_arr[$endpoint][] = $value;
					}
				}
				// $sameAs_arr now contains all sameAs URI's of $person_uri (with duplicates).
				// Loop over all $sameAs_arr URI's to find all their information
				// Add each URI to another array (without duplicates)
				// Check that array everytime a new URI needs to be queried to avoid redundant querying.
				// Check the information for each person, if another sameAs statement is found, add that
				// URI to the $sameAs_arr continue looping.
			}
			
		?>
	</body>
</html>
