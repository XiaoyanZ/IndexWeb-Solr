<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$pagerank = $_REQUEST['method'] == "lucene"? false : true;
$results = false;
$dic = array();
$additionalParameters = array(
'fl' => 'id,title,description',
'wt' => 'json'
);
if($pagerank){
	$additionalParameters["sort"] = "pageRankFile desc";
}

//read the mapping file
$file = fopen("mapNYTimesDataFile.csv","r");
while(! feof($file))
  {
  	$arr = fgetcsv($file);
  	$dic[$arr[0]] = $arr[1];
  }
fclose($file);

if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
  	// $query = "_text_:".$query;
    $results = $solr->search($query, 0, $limit, $additionalParameters);
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>PHP Solr Client Example</title>
	<style>
		a:link {
		    text-decoration: none;
		}

		a:visited {
		    text-decoration: none;
		}

		a:hover {
		    text-decoration: underline;
		}

		a:active {
		    text-decoration: none;
		}
		
		a.title {
			color: #1A0DAB;
			font-size: 18px;
		}

		a.url {
			color: #006621;
			font-size: 14px;
		}

		body {
			font-family: arial, Times, serif;
		}
		.id {
			font-size: 14px;
			color:gray;
		}
	</style>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="method">Sorting Algorithm</label>
      <input type="radio" name="method" value="lucene" checked/>Lucene
      <input type="radio" name="method" value="pagerank"/> PageRank<hr> 
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($_REQUEST['q'], ENT_QUOTES, 'utf-8'); ?>"/>
      <input type="submit"/>
    </form>
<?php

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <lo>
        <table style="border: 10px solid white; text-align: left">
<?php
    // iterate document fields / values
	$title = "";
	$url = "";
	$desc = "";
	$id = "";
    foreach ($doc as $field => $value)
    {
    	if($field == "id"){
    		$id = $value;
    		$value = substr($value, 68);
    		$url = $dic[$value];
    	}else if($field == "title"){
    		$title = $value;
    	}else if($field == "description"){
    		$desc = $value;
    	}
    }
    if(empty($desc)){
    	$desc="N/A";
    }
?>        
		  <tr>
          	<!-- <th>id</th> -->
            <td class="id"><?php echo htmlspecialchars($id, ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>
          <tr>
          	<!-- <th>title</th> -->
            <td><?php echo "<a class='title' href='".$url."' target='_blank'>".htmlspecialchars($title, ENT_NOQUOTES, 'utf-8')."</a>"; ?></td>
          </tr>
          <tr>
         	<!-- <th>url</th> -->
            <td><?php echo "<a class='url' href='".$url."' target='_blank'>".htmlspecialchars($url, ENT_NOQUOTES, 'utf-8')."</a>"; ?></td>
          </tr>
          <tr>
          	<!-- <th>description</th> -->
            <td><?php echo htmlspecialchars($desc, ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>
        </table>
      </lo>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>