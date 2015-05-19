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
		?>
	
		<?php
			$person = "Frank_van_Harmelen";
			$lodDatasets = "http://index.lodlaundromat.org/r2d/";
			$db_prefix = "http://dbpedia.org/resource/";
			$limit = "limit=1000";
			
			$endpoints_query = $lodDatasets . $db_prefix . $person . "?" . $limit;
			
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
			echo "<div style=\"width:350px;height:460px;overflow:scroll;\">Endpoints (n=" . $endpointCount . "):\r\n" . $endpoint_list . "</div>";
			
			// Loop over endpoints to find owl:sameAs statements...
			// ...to do...
		?>
	</body>
</html>