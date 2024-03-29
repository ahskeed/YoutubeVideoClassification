<?php
	// TubeKit Beta 4
	// http://www.tubekit.org
	// Author: Chirag Shah
	// Date: 02/08/2012
	ini_set("memory_limit","100M");
	require_once("config.php");
	require_once("$mpdirectory/rss_fetch.inc");
	require_once("parseRSS.php");

	// Function to insert values into the database
	function AddValue($oTableName, $video, $qid, $timestamp, $today, &$URLArray, &$count, $type, $dbh)
	{
		 $title = $video->title;
		 $query = "";
		 if ($title!="") {  
				    $count++; //count for the related variable
				    $rawdescription = $video->description;
                                    // need to format the description as while inserting into the database,
                                    // mysql cries if extra ' is present in the string
                                    $description = str_replace("'", "#39", $rawdescription);
				    $username = $video->username;
				    $upload_time = $video->published;
				    $duration = $video->length;
				    $category = $video->category;
				    $video_url = $video->watchURL;
				    // Related Video Retrieval 
				    $related_id = substr($video_url,31,11);
				    $URLArray[] = $related_id;
				    $thumb_url = 'http://i1.ytimg.com/vi/' . $related_id . '/0.jpg';
				    $keywords = $video->keywords;
				    $type = $type;
				    $view_count = $video->viewCount;
				    $rank = 0;
				    if($video->numrating >=0 )
				    {
					$rating_count = $video->numrating;
				    }
				    else
				    {
					$rating_count = 0;
				    }
				    $rating_avg = $video->rating;
				    if($video->commentsCount >= 0)
				    {
					$comment_count = $video->commentsCount;
				    }
				    else
				    {
					$comment_count = 0;
				    }
				    $likes = $video->likes;
				    $dislikes = $video->dislikes;
				    $favorited = $video->favoriteCount;	
				    $response_count = $video->responseCount;						
				    $query = "SELECT * from $oTableName WHERE yt_id='$related_id' AND query_id='$qid'";
				    $result_related = mysql_query($query) or mysql_error();
				    $num_rows = mysql_num_rows($result_related);
			  	      
				    if($num_rows == 0){
				     $query = "INSERT INTO $oTableName VALUES('','$qid','$related_id','$timestamp','','','$today', '$type'";
				     $dquery = "SHOW COLUMNS FROM $oTableName";
				     $result_related = mysql_query($dquery,$dbh) or mysql_error();
				     while($line = mysql_fetch_assoc($result_related))
				     {
					     //print_r($line);
					     $fieldName = $line['Field'];
					     switch($fieldName)
					     {
						     case 'title':
							  $query = $query . ",'$title'";
							  break;
						     case 'description':
							  $query = $query . ",'$description'";
							  break;
						     case 'username':
							  $query = $query . ",'$username'";
							  break;
						     case 'upload_time':
							  $query = $query . ",'$upload_time'";
							  break;
						     case 'duration':
							  $query = $query . ",'$duration'";
							  break;
						     case 'category':
							  $query = $query . ",'$category'";
							  break;
						     case 'keywords':
							  $query = $query . ",'$keywords'";
							  break;
						     case 'video_url':
							  $query = $query . ",'$video_url'";
							  break;
						     case 'thumb_url':
							  $query = $query . ",'$thumb_url'";
							  break;
						     case 'view_count':
							  $query = $query . ",'$view_count'";
							  break;
						     case 'rating_count':
							  $query = $query . ",'$rating_count'";
							  break;
						     case 'rating_avg':
							  $query = $query . ",'$rating_avg'";
							  break;
						     case 'comment_count':
							  $query = $query . ",'$comment_count'";
							  break;
						     case 'comments':
							  $query = $query . ",'$comments'";
							  break;
						     case 'response_count':
							  $query = $query . ",'$response_count'";
							  break;
						     case 'favorite_count':
							  $query = $query . ",'$favorited'";
							  break;
						     case 'rank':
							  $query = $query . ",'$rank'";
							  break;			
						     case 'related_id':
						     	  $query = $query . ",'NA'";
							  break;
						    case 'inresponse':
                                                          $query = $query . ",'NA'";
                                                          break;					  
						    case 'likes':
                                                          $query = $query . ",'$likes'";
                                                          break;
                                                    case 'dislikes':
                                                          $query = $query . ",'$dislikes'";
                                                          break;	 
					            default:
							  break;			
					     } // switch($fieldName)
			     } // while($line = mysql_fetch_assoc($resultrelated))						
			     $query = $query . ")";
			     //echo "----------Query------------";
			     //echo $query;
			     }	     
		}
		return $query;
	}
/* -------------------------------------------------------------------------------------------------- */
	$t=getdate();
    	$today_in=date('Y-m-d',$t[0]);
	$qTableName = $prefix . "_queries";
	$oTableName_in = $prefix . "_once";
	
	$query = "SELECT * FROM $qTableName";
	$vresult = mysql_query($query) or die(" ". mysql_error());

	while ($line = mysql_fetch_array($vresult, MYSQL_ASSOC)) 
	{
		$vquery = $line['query'];
		//echo "Processing $vquery...\n";
	    	$vquery = urlencode($vquery);

	    	$qid_in = $line['id'];
	
		$rankDoc = $prefix . "_" . $qid_in . ".ranks";
		$fr = fopen($rankDoc, 'w');
		$rank = 1;
		$maxIndex = $numvideos-49;
		
		// If you're getting throttled by YouTube, you may want to restrict your search results to 
		// a smaller number. You can set it in your database, or right here in the code.
		// If you want to do it in the code, comment the previous line and uncomment the next line.
		// Then set a number in the next line. Currently it's 100.
		//$maxIndex = 100;
		for ($index=1; $index<=$maxIndex; $index+=50)
		{
			// for Categorized values
			$url = "http://gdata.youtube.com/feeds/api/videos?category=$vquery&key=AI39si7-u9DMCmc-oKFmxHEODSij37IP7Wm9o0D_jwHTDm4z7618RCslEBegMM4Cr6VjD2O0-8M8sIFdx9ImxzhR6QzaSQwg9Q&max-results=50&start-index=$index";
			//echo "\tFetching $url\n";
			$rss = fetch_rss($url);

			foreach ($rss->items as $item) 
			{
				$yt_url = $item[link];
				$ytID_in = substr($yt_url,31,11);
	
				$query = "SELECT * from $oTableName_in WHERE yt_id='$ytID_in' AND query_id='$qid_in'";
				$result = mysql_query($query) or mysql_error();
				$num_rows = mysql_num_rows($result);

				// Only if there wasn't already a video with the same ID for the same query, process further                                                                   if ($num_rows == 0)					  
				{
					$feedURL = "http://gdata.youtube.com/feeds/api/videos/$ytID_in?v=2";
					//echo "\t\tExtracting $feedURL\n";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $feedURL);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$page = curl_exec($ch);
					curl_close($ch);
					if($page)
					{
						echo "Feed Url" . "\n";
						echo $feedURL . "\n";
						$entry = simplexml_load_file($feedURL);
						$video = parseVideoEntry($entry);
						$timestamp = time();
						if($video){
						echo "Main Video Link:" . "\n";
						$title = $video->title;
						echo $title ."\n";
						// TODO 
                                                //AddValue($oTableName_in, $video_iv, &$result, $qid_in, $ytID_in, $timestamp_in, $today_in);  
						if ($title!="") {
							$rawdescription = $video->description;	
							// need to format the description as while inserting into the database,
							// mysql cries if extra ' is present in the string
							$description = str_replace("'", "#39", $rawdescription);
							$username = $video->username;
							$upload_time = $video->published;
							$duration = $video->length;
							$category = $video->category;
							$video_url = $video->watchURL;
							$thumb_url = 'http://i1.ytimg.com/vi/' . $ytID_in . '/0.jpg';
							$keywords = $video->keywords;
							$typeMain = "Main";
							$view_count = $video->viewCount;
							if(isset($video->numrating))
							{
								$rating_count = $video->numrating;
							}
							else
							{
								$rating_count = 0;
							}
							$rating_avg = $video->rating;
							if(isset($video->commentsCount))
							{
								$comment_count = $video->commentsCount;
							}
							else
							{
								$comment_count = 0;
							}
							$favorited = $video->favoriteCount;	
							$response_count = $video->responseCount;						
                                                        $likes = $video->likes;
                                                        $dislikes = $video->dislikes;
							$responseURLArray = array();
							$responseList="";
							$count =0;
							// InResponse to retrieval
							if ($video->responsesURL) {
							   $type = "Response:" . $ytID_in;
                                                           $responseFeed = simplexml_load_file($video->responsesURL);
                                                           foreach ($responseFeed->entry as $response) {
                                                           	   $responseVideo = parseVideoEntry($response);
                                                                   $timestamp_in = time();
                                                                   echo "Response Video Title" . "\n";
                                                                   echo $responseVideo->title . "\n";
                                                                   $queryResponse = AddValue($oTableName_in, $responseVideo, $qid_in,
                                                                                    $timestamp_in, $today_in, $responseURLArray,
               									    $count, $type, $dbh);
                                                                   $result = mysql_query($queryResponse) or mysql_error();
                                                         	   } // foreach
                                                           $responseList = implode(",", $responseURLArray);
                                                           } // if
                                            		   
							   $count =0;
                                                           // Related video code
                                                           $commaseparatedURL ="";
                                                           $relatedURLArray = array();
                                                           if ($video->relatedURL) {
                                                              $type = "Related:" . $ytID_in;
                                                              $relatedFeed = simplexml_load_file($video->relatedURL);
                                                              foreach ($relatedFeed->entry as $related) {
                                                              	      $video_in = parseVideoEntry($related);
                                                                      $timestamp_in = time();
                                                                      echo "Related Video Title" . "\n";
                                                                      echo $video_in->title;
                                                                      $queryRelated = AddValue($oTableName_in, $video_in, $qid_in,
                                                                      $timestamp_in, $today_in, $relatedURLArray,
                                                                      $count, $type, $dbh);
                                                                      echo "Related Query:" . "\n";
                                                                      $result = mysql_query($queryRelated) or mysql_error();
                                                                      }
                                                                      $commaseparatedURL = implode(",", $relatedURLArray);
                                                           }// if
					
							// Start formualting the Query
							$query = "INSERT INTO $oTableName_in "; 
							$query = $query. "VALUES('','$qid_in','$ytID_in','$timestamp','','','$today_in','$typeMain'";
							$dquery = "SHOW COLUMNS FROM $oTableName_in";
							$result = mysql_query($dquery) or mysql_error();
							$queryRelated = array();
							while($line = mysql_fetch_assoc($result))
							{
								$fieldName = $line['Field'];
								switch($fieldName)
								{
									case 'title':
										$query = $query . ",'$title'";
										break;
									case 'description':
										$query = $query . ",'$description'";
										break;
									case 'username':
										$query = $query . ",'$username'";
										break;
									case 'upload_time':
										$query = $query . ",'$upload_time'";
										break;
									case 'duration':
										$query = $query . ",'$duration'";
										break;
									case 'category':
										$query = $query . ",'$category'";
										break;
									case 'keywords':
										$query = $query . ",'$keywords'";
										break;
									case 'video_url':
										$query = $query . ",'$video_url'";
										break;
									case 'thumb_url':
										$query = $query . ",'$thumb_url'";
										break;
									case 'view_count':
										$query = $query . ",'$view_count'";
										break;
									case 'rating_count':
										$query = $query . ",'$rating_count'";
										break;
									case 'rating_avg':
										$query = $query . ",'$rating_avg'";
										break;
									case 'comment_count':
										$query = $query . ",'$comment_count'";
										break;
									case 'comments':
										$query = $query . ",'$comments'";
										break;
									case 'response_count':
										$query = $query . ",'$response_count'";
										break;
									case 'favorite_count':
										$query = $query . ",'$favorited'";
										break;
									case 'rank':
										$query = $query . ",'$rank'";
										break;
									case 'related_id':
										$query = $query . ",'$commaseparatedURL'";
										break;
									case 'inresponse':
										echo "InResponseList". "\n";
									        $query = $query . ",'$responseList'";
										break;
									case 'likes':
										$query = $query . ",'$likes'";
                                                                                break;
									case 'dislikes':
										$query = $query . ",'$dislikes'";
                                                                                break; 								
									default:
										break;			
								} // switch($fieldName)
							} // while($line = mysql_fetch_assoc($result))						
							$query = $query . ")";
							$result = mysql_query($query) or mysql_error();		                                                       
							} //title
							}//$video check
						}
				} // if ($num_rows == 0)		
				fwrite($fr, "$rank $ytID_in\n");
				$rank++;
			} // foreach ($rss->items as $item) 
		} // for ($index=1; $index<=51; $index+=50)
		fclose($fr);
	}			
 ?>
