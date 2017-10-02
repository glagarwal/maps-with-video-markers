<?php
$videos = '';
if (!empty($_GET['location'])) {
    /**
     * Here we build the url we'll be using to access the google maps api
     */
    $maps_url = 'https://' .
        'maps.googleapis.com/' .
        'maps/api/geocode/json' .
        '?address=' . urlencode($_GET['location']);
    $maps_json = file_get_contents($maps_url);
    $maps_array = json_decode($maps_json, true);
    $lat = $maps_array['results'][0]['geometry']['location']['lat'];
    $lng = $maps_array['results'][0]['geometry']['location']['lng'];
	
	$loc = $lat.", ".$lng;
    /**
     * Time to make the Youtube api request. We'll build the url using the
     * coordinate values returned by the google maps api
     */
	 if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
	}

	require_once __DIR__ . '/vendor/autoload.php';

	// This code executes if the user enters a search query in the form
	// and submits the form. Otherwise, the page displays the form above.
	if (isset($_GET['keyword'])) {
	  /*
	   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
	  * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
	  * Please ensure that you have enabled the YouTube Data API for your project.
	  */
	  $DEVELOPER_KEY = 'AIzaSyB3Ml9gSnL7Osex3FiyPTzVacQK63AQIb8';
	  
	  $client = new Google_Client();
	  $client->setHttpClient(new GuzzleHttp\Client([ "verify" => false ]));
	  $client->setDeveloperKey($DEVELOPER_KEY);

	  // Define an object that will be used to make all API requests.
      $youtube = new Google_Service_YouTube($client);
      
      try {
        // Call the search.list method to retrieve results matching the specified
        // query term.
        $searchResponse = $youtube->search->listSearch('id,snippet', array(
            'type' => 'video',
            'q' => urlencode($_GET['keyword']),
            'location' =>  $loc,
            'locationRadius' =>  urlencode($_GET['radius'].'km'),
            'maxResults' => 10,
        ));

            
        $videoResults = array();
        # Merge video ids
        foreach ($searchResponse['items'] as $searchResult) {
        array_push($videoResults, $searchResult['id']['videoId']);
        }
        $videoIds = join(',', $videoResults);

        # Call the videos.list method to retrieve location details for each video.
        $videosResponse = $youtube->videos->listVideos('snippet, recordingDetails', array(
        'id' => $videoIds,
        ));

        // $videos = '';
		$latitude1= array();
		$longitude= array();
		$i=0;
        // Display the list of matching videos.
        foreach ($videosResponse['items'] as $videoResult) 
		{
       // $videos .= sprintf('<li><a href=https://www.youtube.com/watch?v=%s> %s </a> (%s,%s) <img src=%s height=60px width=60px /></li>',
			//$videoResult['id'],
            //$videoResult['snippet']['title'],*/
            $latitude1[$i] = $videoResult['recordingDetails']['location']['latitude'];//,
            $longitude1[$i]= $videoResult['recordingDetails']['location']['longitude'];
			//$videoResult['snippet']['thumbnails']['default']['url']);
		
			 /*$latitude1= array($videoResult['recordingDetails']['location']['latitude']);*/
			//$longitude1= array($videoResult['recordingDetails']['location']['longitude']);
			$i++;
			
			

        }
		print_r(array_values($latitude1));
		print_r(array_values($longitude1));
		 
      }
	  
      catch (Google_Service_Exception $e) {
            $error = sprintf('<p>A service error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
				echo $error;
      } 
      catch (Google_Exception $e) {
            $error= sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
				echo $error;
      }

    }        
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Video-Map</title>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="script.js"></script> -->
	<style>
       #map {
        height: 400px;
        width: 100%;
       }
    </style>

	
</head>
<body>
<form action="" method="get">
	<label for="search">Enter Keyword to search: </label>
	<input type="text" id="search" name="keyword" placeholder="video search" /> <br/>
	<label for="loc">Enter a location: </label>
    <input type="text" id="loc" name="location"/><br/>
	<label for="loc">Enter radius: </label>
    <input type="text" id="rad" name="radius"/><br/>
    <button type="submit">Search</button>
</form>
<br/>
<div id="results">
    <?php
    echo '<ul>'.$videos.'</ul>';
    ?>
</div>
<div id="map"></div>
    <script type="text/javascript">
	var latitude2 =  <?php echo json_encode($latitude1) ?>;
	var longitude2 = <?php echo json_encode($longitude1) ?>;
	for (i=0;i<longitude2.length;i++)
		{
		document.write(latitude2[i] + "<br >");
		document.write(longitude2[i] + "<br >");
		
		}
	  
	  
      function initMap()
	  {
        var z = {lat: latitude2[0], lng: longitude2[0]};
        var map = new google.maps.Map(document.getElementById('map'),
		{
          zoom: 5,
          center: z
        }
		);
		var infowindow = new google.maps.InfoWindow();
        var marker, m;
		for(m=0;m<10;m++)
		{
      marker = new google.maps.Marker({
      position: new google.maps.LatLng(latitude2[m], longitude2[m]),
	  map: map
	  
		})
		
		};	
		
      }
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkGbAqakjxQ9N52f-wQ1NVnwuRDLyzlBE&callback=initMap">
    </script>
	
</body>
</html>