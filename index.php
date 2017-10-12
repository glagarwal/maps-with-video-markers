<?php
$videos = '';
if (!empty($_GET['location'])) {
    /**
     * accessing the google maps api using the url and passing the address
     */
    $maps_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($_GET['location'].' '.$_GET['zipcode']);
    $maps_json = file_get_contents($maps_url);
    $maps_array = json_decode($maps_json, true);
    $lat = $maps_array['results'][0]['geometry']['location']['lat'];
    $lng = $maps_array['results'][0]['geometry']['location']['lng'];
	
	$loc = $lat.", ".$lng;
    /**
     * Making the Youtube api request. We build the url using the coordinate values returned by the google maps api
     */
	 if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
	}

	require_once __DIR__ . '/vendor/autoload.php';

	// This code executes if the user enters a search query in the form
	// and submits the form. Otherwise, the page displays the form above.
	// Youtube provides such codes to access use it's API.
	if (isset($_GET['keyword'])) {
	  $DEVELOPER_KEY = 'AIzaSyB3Ml9gSnL7Osex3FiyPTzVacQK63AQIb8';
	  
	  $client = new Google_Client();
	  $client->setHttpClient(new GuzzleHttp\Client([ "verify" => false ]));		//to avoid guzzlehttp errors
	  $client->setDeveloperKey($DEVELOPER_KEY);

	  // Defining an object that will be used to make all API requests.
      $youtube = new Google_Service_YouTube($client);
      
      try {
        // Call to the search.list method to retrieve results matching the specified query term.
        $searchResponse = $youtube->search->listSearch('id,snippet', array(
            'type' => 'video',
            'q' => urlencode($_GET['keyword']),
            'location' =>  $loc,
            'locationRadius' =>  urlencode($_GET['radius'].'km'),
            'maxResults' => 10,
        ));

            
        $videoResults = array();
        # Merging video ids
        foreach ($searchResponse['items'] as $searchResult) {
        array_push($videoResults, $searchResult['id']['videoId']);
        }
        $videoIds = join(',', $videoResults);

        # Call to the videos.list method to retrieve location details for each video.
        $videosResponse = $youtube->videos->listVideos('snippet, recordingDetails', array('id' => $videoIds,));

        // $videos = '';
		$latitude1= array();
		$longitude= array();
		$title1= array();
		$id1= array();
		$thumb1= array();
		
		$i=0;
        // Preparing a formatted string  and arrays of videos fetched above and displaying this list below in html.
        foreach ($videosResponse['items'] as $videoResult) 
		{
       // $videos .= sprintf('<li><a href=https://www.youtube.com/watch?v=%s> %s </a> (%s,%s) <img src=%s height=60px width=60px /></li>',
			$id1[$i]=$videoResult['id'];
            $title1[$i]=$videoResult['snippet']['title'];
            $latitude1[$i] = $videoResult['recordingDetails']['location']['latitude'];//,
            $longitude1[$i]= $videoResult['recordingDetails']['location']['longitude'];
			$thumb1[$i]=$videoResult['snippet']['thumbnails']['default']['url'];
			
			$videos .= sprintf('<li><a href=https://www.youtube.com/watch?v=%s> %s </a> (%s,%s)',
						$id1[$i],
						$title1[$i],
						$latitude1[$i],
						$longitude1[$i]);
						
		
			 /*$latitude1= array($videoResult['recordingDetails']['location']['latitude']);*/
			//$longitude1= array($videoResult['recordingDetails']['location']['longitude']);
			$i++;
			
        }
		//print_r(array_values($latitude1));
		//print_r(array_values($longitude1));
		//print_r(array_values($title1));
		//print_r(array_values($id1));
		//print_r(array_values($thumb1));
		
		 
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
<link rel="stylesheet" href="stylesheet.css">
    <meta charset="utf-8"/>
    <title>Video-Map</title>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="script.js"></script> -->
	<h1>Google Map markers for youtube videos</h1>
	
	<div class="header">
	<h4>~Team Raptors~</h4>
	<img src="d2.png" height="50" width="50" align="right">
	</div>
</head>
<body>
<div id="forminput">
<form action="" method="get">
	<table>
<tr>
	<td>Enter Keyword to search:</td>
	<td><input type="text" id="search" name="keyword" placeholder="Video search" required /><td> <br/>
	</tr>
	<tr>
	<td>Enter a location: </td>
    <td><input type="text" id="loc" name="location" placeholder="Address" required /></td><br/>
	</tr>
	<tr>
	<td>Enter an area zipcode:  </td>
    <td><input type="text" id="zip" name="zipcode" placeholder="optional" /></td><br/>
	</tr>
	<tr>
	<td>Enter the radius </td>
    <td><input type="number" id="rad" name="radius" placeholder="in Km" required /></td><br/>
	</tr>
	<tr><td></td>
</tr>
</table>
<br>
<button type="submit">Search</button>
</form>
</div>
<br/>

<div id="map"></div>
<br/>
<div><ol><?php echo $videos; ?></ol></div>

    <script type="text/javascript">
	var latitude2 =  <?php echo json_encode($latitude1) ?>;
	var longitude2 = <?php echo json_encode($longitude1) ?>;
	var title2 = <?php echo json_encode($title1) ?>;
	var id2 = <?php echo json_encode($id1) ?>;
	var thumb2 = <?php echo json_encode($thumb1) ?>;
	/*for (i=0;i<longitude2.length;i++)
		{
		document.write(latitude2[i] + "<br >");
		document.write(longitude2[i] + "<br >");
		document.write(title2[i] + "<br >");
		document.write(id2[i] + "<br >");
		document.write(thumb2[i] + "<br >");
		
		}*/
	  
	  
      function initMap()
	  {
        var z = {lat: latitude2[0], lng: longitude2[0]};
        var map = new google.maps.Map(document.getElementById('map'),
		{
          zoom: 8,
          center: z
        }
		);
		var infowindow = new google.maps.InfoWindow();
        var marker, m;
		
		for(m=0;m<10;m++)
		{
	
     var marker = new google.maps.Marker({
      position: new google.maps.LatLng(latitude2[m], longitude2[m]),
	  url: 'https://www.youtube.com/watch?v='+ id2[m],
	  map: map,
	  icon: thumb2[m]
	  
		})
		google.maps.event.addListener(marker, 'mouseover', (function(marker, m) 
		{
			return function() 
			{
				infowindow.setContent(title2[m]);
				//infowindow.setOptions({maxWidth: 200});
				infowindow.open(map, marker);
				//window.location.href = marker.url
			}
		})(marker,m))
		google.maps.event.addListener(marker, 'click', (function(marker, m) 
		{
			return function() 
			{
				//window.location.href = marker.url
				window.open(marker.url, '_blank');
			}
		})(marker,m))
		
		};	
		
      }
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCkGbAqakjxQ9N52f-wQ1NVnwuRDLyzlBE&callback=initMap">
    </script>
	
</body>
</html>