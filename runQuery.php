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

// set default for query
$defaultName = 'Frank_van_Harmelen';

// if query input is not set, use Frank van Harmelen as input, otherwise use input
if (isset($_GET['queryInput'])){
    $inputMsg = "Query input is: " . $_GET['queryInput'] ."<br>";
    $person_name = $_GET['queryInput'];
} else {
    $inputMsg = "No input detected, default is: ".$defaultName."<br>";
    $person_name = $defaultName;
}

$lodDatasets = 'http://index.lodlaundromat.org/r2d/';
$lodEndpoint = 'http://ldf.lodlaundromat.org/';
$db_prefix = 'http://dbpedia.org/resource/';
$owlSameAs = 'http://www.w3.org/2002/07/owl#sameAs';
$person_uri = $db_prefix . $person_name;
$limit = 'limit=1000';

$first_query = $lodDatasets . urlencode($person_uri) . "?" . $limit;

echo "<div style=\"height:10%;text-align:center;\">".$inputMsg."<br>" . $person_uri . "</div>";

// Get cURL resource
$curl = curl_init();

// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $first_query,
    CURLOPT_USERAGENT => 'Sample cURL Request'
));

// Send the request & save response to $resp

if(!$first_resp = curl_exec($curl) ){
    echo "CURL FAILED@1<br>";
};

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
    if(! $sameAsObjects = curl_exec($curl) ){
        echo "CURL FAILED@2<br>";
    };
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

echo "<div style=\"height:55%;\">";

// Output $triples_per_uri
$triples_per_uri_str = "<div style=\"float:left;height:100%;width:50%;overflow:scroll;\">Triples from each URI:\r\n";
$triples_per_uri_str .= printArray($triples_per_uri);
$triples_per_uri_str .= "</div>";
echo $triples_per_uri_str;

// Create array that contains all 'unique' properties as keys and whose values are arrays
// containing all their 'unique' possible values as keys and their values represent the
// number of times that particular value is assigned to that property for all sameAs URI's.
// ...
// As the same real world property about a person (e.g., 'gender') does not necessarily have
// the same property name in all datasets (e.g., 'gender', 'hasGender', 'sex', etc.), we somehow
// need to distinguish which properties refer to the same 'real world property'.
// Two methods can be used. The levenshtein algorithm (a function in PHP) can be used to determine
// if a property is referring to the same 'real world property'. If two property names are close
// enough, they are assumed to be the same. This would require some threshold to be set on when
// two properties are 'close enough'. This may not be sufficient for all properties. Additionally,
// a second method can be used in conjunction with the levenhstein algorithm. A synonym list could
// be compiled for each property. Alternately, some function could be used or created which finds all
// synonyms for a particular property name. This last method would be more computationally heavy, but
// may be easier to implement.
// Currently only the levenhstein method will be implemented. The type of data is currently ignored
// ('uri' or 'literal'). Also the language of the literals are ignored (mostly 'en').
$properties_arr = array();
$uri_count = 0;
$threshold = 2;
foreach($triples_per_uri as $uri_data) {
    $uri_name = $uri_data['@uri'];
    // Check if the triples array has data
    if (!is_array($uri_data['@tpl']) || count($uri_data['@tpl']) < 1)
        continue;
    foreach($uri_data['@tpl'] as $key => $properties) {
        // Check if we have reached the properties (triples) array of the given URI
        // Note that it is not necessarily the first element in the array
        if ($key == $uri_name) {
            // Make recursive to deal with properties of properties of properties etc.???
            foreach($properties as $name => $values) {
                $new_prop_name = $name;//getPName($name);
                // One property can have more than one value
                // Need a way to check if a property can only have one value or multiple
                // E.g., gender can only have one (arguably), but rdf:type can have multiple
                foreach($values as $value) {
                    $new_prop_value = $value['value'];//getPName($value['value']);
                    // See if the key already exists in the properties array
                    if (array_key_exists($new_prop_name, $properties_arr)) {
                        // If the property name already exists (wishful thinking),
                        // loop over all values of that property
                        $add_to_array = true;
                        foreach($properties_arr[$new_prop_name] as $prop_val => $prop_count) {
                            // If the value of the current property also already exists,
                            // increase the value of that property value ('occurrences')
                            if ($new_prop_value == $prop_val) {
                                $properties_arr[$new_prop_name][$prop_val]++;
                                $add_to_array = false;
                            // Assume it is the same value if it is 'close enough' according to levenshtein
                            } else if(levenshtein($new_prop_value, $prop_val) <= $threshold) {
                                $properties_arr[$new_prop_name][$prop_val]++;
                                $add_to_array = false;
                            }
                        }
                        // After the loop, assume the value does not exist yet for this property
                        if($add_to_array == true) {
                            $properties_arr[$new_prop_name][$new_prop_value] = 1;
                        }
                    // Check if a 'similar enough' property (name) exists
                    } else {
                        $add_name_to_array = true;
                        foreach($properties_arr as $prop_name => $prop_values) {
                            // A 'similar enough' property name exists
                            if (levenshtein($new_prop_name, $prop_name) <= $threshold) {
                                $add_name_to_array = false;
                                $add_value_to_array = true;
                                // here comes some code duplication...
                                foreach($prop_values as $prop_val => $prop_count) {
                                    // If the value of the current property already exists,
                                    // increase the value of that property value ('occurrences')
                                    if ($new_prop_value == $prop_val) {
                                        $properties_arr[$prop_name][$prop_val]++;
                                        $add_value_to_array = false;
                                    // Assume it is the same value if it is 'close enough' according to levenshtein
                                    } else if(levenshtein($new_prop_value, $prop_val) <= $threshold) {
                                        $properties_arr[$prop_name][$prop_val]++;
                                        $add_value_to_array = false;
                                    }
                                }
                                // Add to array if it does not exist already
                                if ($add_value_to_array == true) {
                                    $properties_arr[$prop_name][$new_prop_value] = 1;
                                }
                            }
                        }
                        // Add to array if it does not exist already
                        if ($add_name_to_array == true) {
                            $properties_arr[$new_prop_name][$new_prop_value] = 1;
                        }
                    }
                }
            }
        }
    }
    $uri_count++;
}

$properties_str = "<div style=\"float:right;height:100%;width:50%;overflow:scroll;\">'Unique' properties with occurrences:\r\n";
$properties_str .= printArray($properties_arr);
$properties_str .= "</div>";
echo $properties_str;

echo "</div>";

// Remove properties below a certain count from the array
// ...to do...
?>
	</body>
</html>
