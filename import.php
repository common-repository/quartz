<?php
require_once('wpframe.php');
wpframe_stop_direct_call(__FILE__);

if(isset($_REQUEST['submit']) and $_REQUEST['submit']) {
	$contents = '';
	
	if($_FILES['textfile']['name']) { //Get uploaded file.
		if($_FILES['textfile']['type'] != 'text/plain') {
			print '<div id="message" class="error fade"><p>Please upload a text file.</p></div>';
		} else {
			$contents = file_get_contents($_FILES['textfile']['tmp_name']);
		}
	
	} elseif($_REQUEST['text-url']) { //Get the remote text file.
		$remote_file = load($_REQUEST['text-url'], array('return_info'=>true, 'cache'=>true));
		if($remote_file['headers']['Content-Type'] != 'text/plain') {
			print '<div id="message" class="error fade"><p>Please upload a text file.</p></div>';
		} else {
			$contents = $remote_file['body'];
		}
	}
	
	if($contents) {
		$lines = preg_split('/\r|\n/', $contents); //Split the file line by line
		$quotes = array();
		foreach($lines as $q) {
			$q = trim($q);
			if($q) array_push($quotes, "'" . $wpdb->escape($q) . "', '1'"); //Create the 'big query' - we'll import all the queries in one go.
		}
		$import_query = "INSERT INTO {$wpdb->prefix}quartz_quote(quote,status) VALUES ( " . implode('), (', $quotes) . " )";
		$wpdb->query($import_query);
		print '<div id="message" class="updated fade"><p>'. $wpdb->rows_affected .' Quotes imported successfully.</p></div>';
	}
}
 
?>

<div class="wrap">
<h2><?php e("Import Quotes"); ?></h2>

<form name="post" action="" method="post" id="post" enctype="multipart/form-data">
<div id="poststuff">

<p><?php e("You can import quotes from a text file. Each line in the text file must have a quote in it. An example is the <a href='http://binnyva.com/pro/dos/boot_booster/quotes/celebrity.txt'>Celebrity Quotes File</a>. You can import that file by entering the value 'http://binnyva.com/pro/dos/boot_booster/quotes/celebrity.txt' in the 'Remote Text File' field."); ?></p>

<div class="postbox">
<h3 class="hndle"><span><?php e("Upload Text File"); ?></span></h3>
<div class="inside">
<input type="file" name="textfile" />
</div></div>

<p>OR</p>

<div class="postbox">
<h3 class="hndle"><span><?php e("Remote Text File"); ?></span></h3>
<div class="inside">
<input type="text" name="text-url"  />
</div></div>

<p class="submit">
<input type="hidden" name="action" value="import" />
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<span id="autosave"></span>
<input type="submit" name="submit" value="<?php e('Import') ?>" style="font-weight: bold;" tabindex="4" />
</p>

</div>
</form>

<a href="edit.php?page=quartz/all_quotes.php">Manage Quotes</a>

</div>

<?php
/////////////////////////////////////////////////// Library Functions ///////////////////////////////////

/**
 * Link: http://www.bin-co.com/php/scripts/load/
 * Version : 2.00.A
 */
function load($url,$options=array()) {
	$default_options = array(
		'method'		=> 'get',
		'return_info'	=> false,
		'return_body'	=> true,
		'cache'			=> false		
	);
	// Sets the default options.
	foreach($default_options as $opt=>$value) {
		if(!isset($options[$opt])) $options[$opt] = $value;
	}

    $url_parts = parse_url($url);
    $ch = false;
    $info = array(//Currently only supported by curl.
        'http_code'    => 200
    );
    $response = '';
    
    $send_header = array(
        'Accept' => 'text/*',
        'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
    );
    
    if($options['cache']) {
    	$cache_folder = '/tmp/php-load-function/';
    	if(!file_exists($cache_folder)) mkdir($cache_folder, 0777);
    	if(isset($options['cache_folder'])) $cache_folder = $options['cache_folder'];
    	
    	$cache_file_name = str_replace(array('http://', 'https://'),'', $url);
    	$cache_file_name = str_replace(
    		array('/','\\',':','?','&','='), 
    		array('_','_','-','.','-','_'), $cache_file_name);
    	$cache_file = joinPath($cache_folder, $cache_file_name); //Don't change the variable name - used at the end of the function.
    	
    	if(file_exists($cache_file)) { // Cached file exists - return that.
    		$response = file_get_contents($cache_file);
    		
    		 //Seperate header and content
			$separator_position = strpos($response,"\r\n\r\n");
			$header_text = substr($response,0,$separator_position);
			$body = substr($response,$separator_position+4);
			
			foreach(explode("\n",$header_text) as $line) {
				$parts = explode(": ",$line);
				if(count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
			}
			$headers['cached'] = true;
			
    		if(!$options['return_info']) return $body;
    		else return array('headers' => $headers, 'body' => $body, 'info' => array('cached'=>true));
    	}
    }

    ///////////////////////////// Curl /////////////////////////////////////
    //If curl is available, use curl to get the data.
    if(function_exists("curl_init") 
                and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options
        
        if(isset($options['post_data'])) { //There is an option to specify some data to be posted.
        	$page = $url;
        	$options['method'] = 'post';
        	
        	if(is_array($options['post_data'])) { //The data is in array format.
				$post_data = array();
				foreach($options['post_data'] as $key=>$value) {
					$post_data[] = "$key=" . urlencode($value);
				}
				$url_parts['query'] = implode('&', $post_data);
			
			} else { //Its a string
	        	$url_parts['query'] = $options['post_data'];
			}
        } else {
			if(isset($options['method']) and $options['method'] == 'post') {
				$page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
			} else {
				$page = $url;
			}
		}

        if(!isset($options['curl_handle']) or !$options['curl_handle']) $ch = curl_init($url_parts['host']);
        else $ch = $options['curl_handle'];
        
        curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
        curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
        curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body'])); //The content - if true, will not download the contents. There is a ! operation - don't remove it.
        if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
        }
        //Set the headers our spiders sends
        curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); //The Name of the UserAgent we will be using ;)
        $custom_headers = array("Accept: " . $send_header['Accept'] );
        if(isset($options['modified_since']))
            array_push($custom_headers,"If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);

        curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt"); //If ever needed...
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        if(isset($url_parts['user']) and isset($url_parts['pass'])) {
            $custom_headers = array("Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch); //Some information on the fetch
        if(!isset($options['curl_handle'])) curl_close($ch); //Dont close the curl session if the curl handle is passed. We may need it later.

    //////////////////////////////////////////// FSockOpen //////////////////////////////
    } else { //If there is no curl, use fsocketopen
        if(isset($url_parts['query'])) {
            if(isset($options['method']) and $options['method'] == 'post')
                $page = $url_parts['path'];
            else
                $page = $url_parts['path'] . '?' . $url_parts['query'];
        } else {
            $page = $url_parts['path'];
        }

        $fp = fsockopen($url_parts['host'], 80, $errno, $errstr, 30);
        if ($fp) {
            $out = '';
            if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                $out .= "POST $page HTTP/1.1\r\n";
            } else {
                $out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
            }
            $out .= "Host: $url_parts[host]\r\n";
            $out .= "Accept: $send_header[Accept]\r\n";
            $out .= "User-Agent: {$send_header['User-Agent']}\r\n";
            if(isset($options['modified_since']))
                $out .= "If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])) ."\r\n";

            $out .= "Connection: Close\r\n";
            
            //HTTP Basic Authorization support
            if(isset($url_parts['user']) and isset($url_parts['pass'])) {
                $out .= "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']) . "\r\n";
            }

            //If the request is post - pass the data in a special way.
            if(isset($options['method']) and $options['method'] == 'post' and $url_parts['query']) {
                $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                $out .= "\r\n" . $url_parts['query'];
            }
            $out .= "\r\n";

            fwrite($fp, $out);
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
            }
            fclose($fp);
        }
    }

    //Get the headers in an associative array
    $headers = array();

    if($info['http_code'] == 404) {
        $body = "";
        $headers['Status'] = 404;
    } else {
        //Seperate header and content
        $separator_position = strpos($response,"\r\n\r\n");
        $header_text = substr($response,0,$separator_position);
        $body = substr($response,$separator_position+4);
        
        foreach(explode("\n",$header_text) as $line) {
            $parts = explode(": ",$line);
            if(count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
        }
    }
    
    if(isset($cache_file)) { //Should we cache the URL?
    	file_put_contents($cache_file, $response);
    }

    if($options['return_info']) return array('headers' => $headers, 'body' => $body, 'info' => $info, 'curl_handle'=>$ch);
    return $body;
}

/**
 * Takes one or more file names and combines them, using the correct path separator for the 
 * 		current platform and then return the result.
 * Arguments: The parts that make the final path.
 * Example: joinPath('/var','www/html/','try.php'); // returns '/var/www/html/try.php'
 */
function joinPath() {
	$path = '';
	$arguments = func_get_args();
	$args = array();
	foreach($arguments as $a) if($a) $args[] = $a;//Removes the empty elements
	
	$arg_count = count($args);
	for($i=0; $i<$arg_count; $i++) {
		$folder = $args[$i];
		
		if($i != 0 and $folder[0] == DIRECTORY_SEPARATOR) $folder = substr($folder,1); //Remove the first char if it is a '/' - and its not in the first argument
		if($i != $arg_count-1 and substr($folder,-1) == DIRECTORY_SEPARATOR) $folder = substr($folder,0,-1); //Remove the last char - if its not in the last argument
		
		$path .= $folder;
		if($i != $arg_count-1) $path .= DIRECTORY_SEPARATOR; //Add the '/' if its not the last element.
	}
	return $path;
}