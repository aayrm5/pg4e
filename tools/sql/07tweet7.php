<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/quickstart.html
require_once "names.php";
require_once "text_util.php";
require_once "es_util.php";

if ( ! pg4e_user_es_load($LAUNCH) ) return;

// Compute the stuff for the output
$code = getCode($LAUNCH);

$lines = get_lines($code, 5);
$gin = get_gin($lines);
ksort($gin);

// Find the longest word
$word = '';
foreach($gin as $keyword => $docs) {
    if(strlen($keyword) > strlen($word)) $word = $keyword;
}

$oldgrade = $RESULT->grade;

if ( U::get($_POST,'check') ) {

	$client = get_es_connection();
    if ( ! $client ) return;

    $word = 'parentheses';

	$params = [
    	'index' => $es_prefix .'/' . $es_user,
    	'body'  => [
        	'query' => [
            	'match' => [
                	'text' => $word
            	]
        	]
    	]
	];
	$_SESSION['last_parms'] = $params;

	try {
		unset($_SESSION['last_response']);
		$response = $client->search($params);
		$_SESSION['last_response'] = $response;
		// echo("<pre>\n");print_r($response);echo("</pre>\n");die();
		$hits = count($response['hits']['hits']);
		if ( $hits < 1 ) {
			$_SESSION['error'] = 'Query / match did not find '.$word;
        	header( 'Location: '.addSession('index.php') ) ;
        	return;
    	}
			
	} catch(Exception $e) {
		$_SESSION['error'] = 'Error: '.$e->getMessage();
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $gradetosend = 1.0;
    $pg_PDO = false;
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<h1>Elastic Search Tweets</h1>
<p>
In this assignment you will create an elastic search index 
in the following Elastic Search instance:
<?php 
// es_getexpire();
pg4e_user_es_elastic7($LAUNCH); 
pg4e_user_es_form($LAUNCH); 
?>
</p>
The index name should be the same as your user name and you should drop the index
before you insert
the following tweets (The author and date can be anything):
<p>
<pre>
<?php 
foreach($lines as $line) { 
  echo($line."\n");
}
?>
</pre>
</p>
<p>
You can build your application by starting with this code -
<a href="https://www.pg4e.com/code/elastic7/elastic0.py" target="_blank">https://www.pg4e.com/code/elastic7/elastic0.py</a>.
You will need to setup the <b>hidden.py</b> with your elastic search host/port/account/password values.
</p>
<p>
You will need to install the Python ElasticSearch library:
<pre>
pip install elasticsearch    # or pip3
</pre>
</p>
<p>
This autograder will run a command equivalent to using the <b>elastictool.py</b> command as follows:
<pre>
elastic7 csev$ python3 elastictool.py

Enter command: <b>search conversation</b>
http://testing:*****@34.219.107.86:8001/v1/basicauth/elasticsearch/testing/_search?pretty
{"query": {"query_string": {"query": "conversation"}}}
200
{
  "took": 4,
  "timed_out": false,
  "_shards": {
    "total": 5,
    "successful": 5,
    "skipped": 0,
    "failed": 0
  },
  "hits": {
    "total": {
      "value": 1,
      "relation": "eq"
    },
    "max_score": 0.2876821,
    "hits": [
      {
        "_index": "testing",
        "_type": "_doc",
        "_id": "4",
        "_score": 0.2876821,
        "_source": {
          "author": "kimchy",
          "text": "The conversation was going so well for a while and then you made the",
          "timestamp": "2020-02-20T19:32:48.847651"
        }
      }
    ]
  }
}

Enter command:
</pre>
And expect to get at least one hit.
</p>
<p>
You can download <b>elastictool.py</b> at 
<a href="https://www.pg4e.com/code/elastictool.py" target="_blank">https://www.pg4e.com/code/elastictool.py</a>.
You will need to setup the <b>hidden.py</b> with your elastic search host/port/account/password values.
</p>
<!--
<?php
  if ( isset($_SESSION['last_parms']) ) {
	$jsonstr = json_encode($_SESSION['last_parms'], JSON_PRETTY_PRINT);
	unset($_SESSION['last_parms']);
	echo("Last elastic search query:\n\n");
	echo(htmlentities($jsonstr, ENT_NOQUOTES));
	echo("\n\n");
  }
  if ( isset($_SESSION['last_response']) ) {
	$jsonstr = json_encode($_SESSION['last_response'], JSON_PRETTY_PRINT);
	unset($_SESSION['last_response']);
	echo("Last elastic search response:\n\n");
	echo(htmlentities($jsonstr, ENT_NOQUOTES));
	echo("\n");
 }

?>
-->
<?php
if ( $LAUNCH->user->instructor ) {
  echo("<p>Note to instructors: Students can view source to see the last elastic search request and response</p>");
}
?>

