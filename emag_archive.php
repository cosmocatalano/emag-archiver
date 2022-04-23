<?php

//*** FUNCTIONS ***
//smaller pieces of the archive process that you can feel free to look at or not
//NB: the SETTINGS section below has thing you need to change

//curl request that pulls the full page source
function page_curl($url) {

	//headers that we probably don't need but that make the script more human looking
	//feel free to edit as needed/desired
	$headers = array();
	$headers[] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
	$headers[] = 'accept-encoding: gzip, deflate, br';
	$headers[] = 'accept-language: en-US,en;q=0.9';
	$headers[] = 'cache-control: no-cache';
	$headers[] = 'pragma: no-cache';
	$headers[] = 'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"';
	$headers[] = 'sec-ch-ua-mobile: ?0';
	$headers[] = 'sec-ch-ua-platform: "macOS"';
	$headers[] = 'sec-fetch-dest: document';
	$headers[] = 'sec-fetch-mode: navigate';
	$headers[] = 'sec-fetch-site: none';
	$headers[] = 'sec-fetch-user: ?1';
	$headers[] = 'upgrade-insecure-requests: 1';
	$headers[] = 'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36';

	//set up the cURL request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

	$emag_return = curl_exec($ch);
	if (curl_errno($ch)) {
	    echo "\n***\nError:".curl_error($ch)."\n***\n";
	} else {
		echo "Successful cURL to ".$url."\n";
	}
	curl_close($ch);
	return $emag_return;
}

//find the publication id
//NB: this regex also gives you your publication's plid if you want it (e.g. echo $matches[2];)
function find_id($source, $base_url){
	preg_match('/(\/library\?plid=([0-9]*?))"/', $source, $matches);
	return $base_url.$matches[1];
}

//takes a bunch of HTML and returns an array of per-issue information
function make_issues_list($library_source) {

	//dirty/brittle regex hack
	$library_chunk = explode('class="issueList', $library_source);
	$issues_chunk = explode('</main>', $library_chunk[1]);
	$html_raw = $issues_chunk[0];

	//patterns to match for the issue data
	$patt_id = '/\/viewissue\/([0-9]*?)\?cs=library/';
	$patt_date = '/https:\/\/.*?\/([0-9]*?)\/.*?\.jpg/';
	$patt_title = '/>(.*?)<\/div>/';

	//getting the matches
	preg_match_all($patt_id, $html_raw, $matches_id);
	preg_match_all($patt_date, $html_raw, $matches_date);
	preg_match_all($patt_title, $html_raw, $matches_title);

	//array to put store issue data
	$issue_array = array();

	//iterator ties per-issue data toegether
	$i = 0;

	//looking through the matches and storing each issues data
	foreach($matches_id[1] as $issue) {
		$issue_array[$i]['id'] = $issue;
		$issue_array[$i]['date'] = $matches_date[1][$i];
		$issue_array[$i]['readible'] = preg_replace("/^(\d{4})(\d{2})(\d{2})$/", "$1-$2-$3", $matches_date[1][$i]);
		$issue_array[$i]['thumb'] = $matches_date[0][$i];
		$issue_array[$i]['title'] = $matches_title[1][$i];
		$i++;
	}
	return $issue_array;
}

//finds the magazine download link
//NB: this makes certain assumptions about how the name of your publication is rendered in URLs
//see the $pub_string setting below
function find_link($pub_string, $source, $base_url) {
	preg_match("/\/$pub_string\/\S*?\.pdf/", $source, $matches);
	return $base_url.$matches[0];
}

//downloads the actual magazine PDF
function get_mag($url, $date, $title, $save_dir) {
	$return = page_curl($url);
	file_put_contents($save_dir.$date.'.pdf', $return);
	echo "downloaded ".$title." to ".$date.".pdf\n";
}

//putting all the other functions together to download/index each issue
function each_issue($pub_string, $base_url_archive, $id, $title, $readible, $base_url_reader, $save_dir) {
	$url =  $base_url_archive.'/archive/viewissue/'.$id.'?cs=library';
	
	//getting the reader page
	$reader_source = page_curl($url);
	
	//extracting the PDF link
	//NB: probably uses a different $base_url value than is set in SETTINGS
	$pdf_link = find_link($pub_string, $reader_source, $base_url_reader);
	
	//downloading the magainze
	get_mag($pdf_link, $pub_string.'_'.$readible, $title, $save_dir);

}

//*** END FUNCTIONS ***

//*** SETTINGS ***
//THINGS YOU SHOULD (OR HAVE TO) CHANGE HERE

//ADJUST THE MEMORY LIMIT
//magainze PDFs can get very big!
//NB: this disables your memory limit entirely
ini_set('memory_limit','-1');    

//ACCESS TO THE PUBLICATION
// *** YOU HAVE TO CHANGE THIS ***
//what's your unique login link?
//I accessed mine by providnig my username/password at the publication's "normal" webpage and they provided me with a link to the archive page
//NB: this almost certainly UNIQUELY IDENTIFIES YOU as the person acrhiving all their PDFs. Don't do anything I wouldn't do.
//e.g. $access_url = 'https://archive.publication-name.com/Account/VipLogin/D578QBA1-RANDOM-GH54-LOOKING-80D1-IDENTIFIER-9422'
$access_url = '';

//MACHINE-FRIENDLY PUBLICATION NAME
// *** YOU HAVE TO CHANGE THIS ***
//what's the string the website uses for your publication? 
//check the page source, if the links look like https://archive.publication-name.com/, this value will probably the "publication-name" part
//e.g. $pub_string = "publication-name";
$pub_string = '';

//ARCHIVE LOCATION ON YOUR OWN COMPUTER
//where are these magazine PDFs getting saved?
//e.g. $save_dir = '/Volumes/Your-External-Drive/Publication-Name/';
$save_dir = '';  //this selects whatever directory the script is currently running from

//ARCHIVE PAGE URL BASE
//the full  domain name of the "regular" looking webpage that contains thubmnails of all the issues
//NB: this may vary publication to publication, contains https and no trailing slash
$base_url_archive = "https://archive.".$pub_string.".com";

//READER PAGE URL BASE
//the full domain name of the "custom" looking reader webpage that lets you thumb through the pages of the actual issues
//NB: this may vary publication to publication, contains https and no trailing slash
$base_url_reader = "https://reader.".$pub_string.".com";

//FASTEST RESPONSE IN MICROSECTIONS
//this defaults to 7.46 seconds, but you can change it if you want
//NB: keeping this a few seconds or hire makes you not seem like an attacker
$response_min = 7346000;

//SLOWEST REPSONSE IN MICROSECONDS
//this defaults to 11.361 seconds, but you can change it if you want
//NB: keeping this down helps this script not take forever to run
$response_max = 11361000;

//*** END SETTINGS ***


//*** THE SCRIPT ***
//MAKING YOUR SETTINGS WORK WITH THE FUNCTIONS ABOVE

//get the access page
$access_source = page_curl($access_url);

//get the library page link and go there
//this recreates a window.location redirect on the live page
$library_url = find_id($access_source, $base_url_archive);
$library_source = page_curl($library_url);

//converting HTML page to issues data array
$issue_array = make_issues_list($library_source);

//in case you want to do just a small portion of a potentially very large array
// e.g. $download_array = array_slice($issue_array, 178, 6);
$download_array = $issue_array;  

//turning the crank
$i = 0;  
foreach($download_array as $issue) {
	echo "attempting ".$issue['title']." (uid ".$issue['id'].", array position ".$i." of ".(count($download_array)-1).")\n";

	//finding the magazine PDF link for a given issue and downloading that file do your local machine
	each_issue($pub_string, $base_url_archive, $issue['id'], $issue['title'], $issue['readible'], $base_url_reader, $save_dir);
    
	//indexing the downloaded issue and its metadata in a CSV file called "emag_index-[publication-name]"
	$fp = fopen($save_dir.'emag_index-'.$pub_string.'.csv', 'a');
	fputcsv($fp, $issue);
	fclose($fp);
	echo "added to index\n";

	//slowing and randomizing a bit to prevent being blacklisted as an attack
	$wait_for = mt_rand($response_min, $response_max);
	echo "pretending to be human for ".($wait_for/1000000)." seconds\n\n\n";
	usleep($wait_for);
	$i++;
}

//letting you know everything finished running successfully 
echo "acrhive script completed\n";

//*** END SCRIPT ***

?>