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
			$uri_arr = array();
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
				
				// Reset the query for the subjects
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
				
				// Insert sameAs (subjects) URI's into array (no duplicate check yet)
				foreach($sameAsObjects_arr[0] as $key => $value) {
					if (/*key == object*/) {
						$uri_arr[] = $value;
						$uri_arr[] = $endpoint;
					}
				}
				// Insert sameAs (objects) URI's into array
				foreach($sameAsSubjects_arr[0] as $key => $value) {
					if (/*key == subject*/) {
						$uri_arr[] = $value;
						$uri_arr[] = $endpoint;
					}
				}
			}
			
			// Loop over all $sameAs_arr URI's to find all their triples
			$uri_arr_unique = array();
			$triples_per_uri = array(array());
			$uri_count = 0;
			for ($i = 0; $i < count($uri_arr) - 1; $i += 2) {
				$uri_value = $uri_arr[$i];
				$uri_endpo = $uri_arr[($i + 1)];
				$uri_query = $lodEndpoint . $uri_endpo . '?subject=' . urlencode($uri_value) . '&predicate=' . '&object=';
				
				// Check if this URI has already been queried
				// Add to the list if it is not.
				if (in_array($uri_value, $uri_arr_unique))
					continue;
				else
					$uri_arr_unique[] = $uri_value;
				
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $uri_query,
					CURLOPT_USERAGENT => 'Sample cURL Request',
					CURLOPT_HTTPHEADER => array('Accept: application/json')
				));
				$query_resp = curl_exec($curl);
				$query_arr = json_decode($query_resp, true);
				
				// Add the person with its endpoint and triples to a twodimensional array
				$triples_per_uri[$uri_count][] = $uri_value;
				$triples_per_uri[$uri_count][] = $uri_endpo;
				$triples_per_uri[$uri_count][] = $query_arr;
				$uri_count++;
				
				// Check if this URI (array of triples) contains new sameAs statements
				// ...
				// If it does add the object URI of that/those statement(s) to the $uri_arr.
				// Check to see if the $uri_arr does not already contain this/these new URI('s)
			}
			
			// Output $triples_per_uri
			// $triples_per_uri_str = "<div style=\"height:300px;overflow:scroll;\">";
			// $triples_per_uri_str .= printArray($triples_per_uri);
			// $triples_per_uri_str .= "</div>";
			// echo $triples_per_uri_str;
		?>
	</body>
</html>
