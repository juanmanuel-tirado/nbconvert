<?php
   /*
   Plugin Name: NbConvert
   Description: A plugin to add ipynb files to a blog post or page using nbviewer
   Version: 1.0
   Author: Andrew Challis
   Author URI: http://www.andrewchallis.com
   License: MIT
   */

function nbconvert_handler($atts) {
  //run function that actually does the work of the plugin
  $nb_output = nbconvert_function($atts);
  //send back text to replace shortcode in post
  return $nb_output;
}



function nbconvert_get_sha($url) {

  $url_list = explode('/', $url);

  $owner = $url_list[1];
  $repo = $url_list[2];
  $branch = $url_list[4];
  $path = implode("/", array_slice($url_list, 5));

  $request_url = 'https://api.github.com/repos/'.$owner.'/'.$repo.'/commits/'.$branch.'?path='. $path.'&page=1';

  $context_params = array(
    'http' => array(
      'method' => 'GET',
      'user_agent' => 'Bogus user agent',
      'timeout' => 1
    )
  );


  $res = file_get_contents($request_url, FALSE, stream_context_create($context_params));

  $sha = json_decode($res, true)['sha'];

  return $sha;
}

function nbconvert_function($atts) {
  //process plugin

  extract(shortcode_atts(array(
        'url' => "",
     ), $atts));


  $clean_url = preg_replace('#^https?://#', '', rtrim($url,'/'));

  // get the latest sha
  $sha = nbconvert_get_sha($clean_url);

  $url_list = explode('/', $clean_url);

  $owner = $url_list[1];
  $repo = $url_list[2];
  $branch = $url_list[4];
  $path = implode("/", array_slice($url_list, 5));

  // build the url with the latest sha
  $nbviewer_url = 'https://nbviewer.jupyter.org/github/'.$owner.'/'.$repo.'/blob/'.$sha.'/'.$path;

  $html = file_get_contents($nbviewer_url);

  $nb_output = nbconvert_getHTMLByID('notebook-container', $html);

  //$last_update_date_time = nbconvert_get_most_recent_git_change_for_file_from_api($url);

  $converted_nb = '<div class="notebook">
    <div class="nbconvert-labels">
      <label class="github-link">
        <a href="'.$url.'" target="_blank">Check it out on github</a>
      </label>
      <label class="github-link">
        <a href="'.$nbviewer_url.'" target="_blank">Check it out on NBViewer</a>
      </label>
      </div>
    <div class="nbconvert">'.$nb_output.'
    </div>
  </div>';

  //send back text to calling function
  return $converted_nb;
}

function nbconvert_innerHTML(DOMNode $elm) {
  $innerHTML = '';
  $children  = $elm->childNodes;

  foreach($children as $child) {
    $innerHTML .= $elm->ownerDocument->saveHTML($child);
  }

  return $innerHTML;
}



function nbconvert_getHTMLByID($id, $html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    $node = $dom->getElementById($id);
    if ($node) {
        $inner_output = nbconvert_innerHTML($node);
        return $inner_output;
    }
    return FALSE;
}


function nbconvert_enqueue_style() {
	wp_enqueue_style( 'NbConvert', plugins_url( '/css/nbconvert.css', __FILE__ ));
}

add_action( 'wp_enqueue_scripts', 'nbconvert_enqueue_style' );
add_shortcode("nbconvert", "nbconvert_handler");
