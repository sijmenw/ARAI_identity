<!DOCTYPE HTML>
<html lan="en">
	<head>
	<meta charset="utf-8" />
	<style type="text/css">
		body, html{
			height: 100%;
			margin: 0;
			padding: 0;
		}
	</style>
	</head>
	
	<body>

<?php
// include functions
include_once 'functions.php';

// if query input is not set, use Frank van Harmelen as input, otherwise use input
if (isset($_POST['queryInput'])){
    $person_name = $_POST['queryInput'];
} else {
    $person_name = 'Frank_van_Harmelen';
}

$lodDatasets = 'http://index.lodlaundromat.org/r2d/';
$lodEndpoint = 'http://ldf.lodlaundromat.org/';
$db_prefix = 'http://dbpedia.org/resource/';
$owlSameAs = 'http://www.w3.org/2002/07/owl#sameAs';
$person_uri = $db_prefix . $person_name;
$limit = 'limit=1000';

$first_query = $lodDatasets . urlencode($person_uri) . "?" . $limit;

echo "<div style=\"height:10%;text-align:center;\">" . $person_uri . "</div>";

// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $first_query,
    CURLOPT_USERAGENT => 'Sample cURL Request'
));
// Send the request & save response to $resp
$first_resp = curl_exec($curl);
// Close request to clear up some resources
curl_close($curl);

// Parse the response into an array
$first_resp_arr = parseResponseResults($first_resp);

// Place all endpoints in a list
$endpoint_list = '<ol type="1">';
foreach ($first_resp_arr as $value) {
    $endpoint_list .= '<li>' . $value . '</li>';
}
$endpoint_list .= '</ol>';

// Nest Endpoints and sameAs URI's in the same div
echo "<div style=\"height:35%;\">";

// Print out endpoints
echo "<div style=\"float:left;height:100%;width:50%;overflow:scroll;\">Endpoints containing the given URI:\r\n" . $endpoint_list . "</div>";

// Loop over endpoints to find owl:sameAs statements...
$uri_arr = array();
foreach ($first_resp_arr as $endpoint) {
    $sameAsObjectsQuery = $lodEndpoint . $endpoint . '?subject=' . urlencode($person_uri) . '&predicate=' . urlencode($owlSameAs) . '&object=';
    $sameAsSubjectsQuery = $lodEndpoint . $endpoint . '?subject=' . '&predicate=' . urlencode($owlSameAs) . '&object=' . urlencode($person_uri);

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
    //$sameAsObjects_str = "<div style=\"height:300px;overflow:scroll;\">";
    //$sameAsObjects_str .= printArray($sameAsObjects_arr);
    //$sameAsObjects_str .= "</div>";
    //echo $sameAsObjects_str;

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
    //$sameAsSubjects_str = "<div style=\"height:300px;overflow:scroll;\">";
    //$sameAsSubjects_str .= printArray($sameAsSubjects_arr);
    //$sameAsSubjects_str .= "</div>";
    //echo $sameAsSubjects_str;

    // Insert sameAs (subjects) URI's into array
    // First check if any sameAs statements have been found
    if (array_key_exists('@graph', $sameAsObjects_arr) && array_key_exists($owlSameAs, $sameAsObjects_arr['@graph'][0])) {
        foreach($sameAsObjects_arr['@graph'][0][$owlSameAs] as $value) {
            if (is_array($value)) {
                foreach($value as $item) {
                    if (!in_array($item, $uri_arr)) {
                        $uri_arr[] = $item;
                    }
                }
            } else if (!in_array($value, $uri_arr)) {
                $uri_arr[] = $value;
            }
        }
    }

    // Insert sameAs (objects) URI's into array
    if (array_key_exists('@graph', $sameAsSubjects_arr)) {
        foreach($sameAsSubjects_arr['@graph'] as $value) {
            if (array_key_exists($owlSameAs, $value) && !in_array($value['@id'], $uri_arr)) {
                $uri_arr[] = $value['@id'];
            }
        }
    }
}

echo "<div style=\"float:right;height:100%;width:50%;overflow:scroll;\">owl:sameAs URI's (might be incomplete; triples list should be complete):\r\n" . printArray($uri_arr) . "</div>";

// Close nested divs
echo "</div>";

// Loop over all $uri_arr URI's to find all their triples
$triples_per_uri = array(array());
$uri_count = 0;
for ($i = 0; $i < count($uri_arr); $i += 1) {
    $uri_value = $uri_arr[$i];

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $uri_value,
        CURLOPT_USERAGENT => 'Sample cURL Request',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => array('Accept: application/json')
    ));
    $last_resp = curl_exec($curl);
    $last_resp_arr = json_decode($last_resp, true);

    // Add the person with its triples to a two-dimensional array
    $triples_per_uri[$uri_count]['@uri'] = $uri_value;
    $triples_per_uri[$uri_count]['@tpl'] = $last_resp_arr;
    $uri_count++;

    // Check if this URI (array of triples) contains new sameAs statements
    // If it does add the object URI of that/those statement(s) to the $uri_arr.
    addSameAsStatements($uri_value, $owlSameAs, $last_resp_arr, $uri_arr);
}

// Output $triples_per_uri
$triples_per_uri_str = "<div style=\"height:55%;overflow:scroll;\">Triples:\r\n";
$triples_per_uri_str .= printArray($triples_per_uri);
$triples_per_uri_str .= "</div>";
echo $triples_per_uri_str;

?>
	</body>
</html>
