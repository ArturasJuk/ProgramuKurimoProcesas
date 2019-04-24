<?php
/**
 * @author Prif163 <arturas.juknevicius@stud.vgtu.lt>
 * @copyright    Copyright (C) 2019 VGTU. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */

 /**
* \brief transforms relative links to absolute links`
* @param $rel linked passed to functioned. assumed to be realtive.
* @param $base URL where the relative linkw as found
* @return  absolute link
* The function checks if the passed, assumed relative, link is actually relative. If it'scandir
* absolute it returns the passed link, otherwise it transforms teh link to an absolute link
*/
  function rel2abs($rel, $base)
  {
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;    /**< return if already absolute URL */

    if($rel == '#')return $base; /**< empty section link, can be ignored*/

    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel; /**< queries and anchors */

    extract(parse_url($base)); /**< parse base URL and convert to local variables: $scheme, $host, $path */

    $path = preg_replace('#/[^/]*$#', '', $path); /**< remove non-directory element from path */

    if ($rel[0] == '/') $path = ''; /**< destroy path if relative url points to root */

    $abs = "$host$path/$rel"; /**< dirty absolute URL */

    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'); /**< replace '//' or '/./' or '/foo/../' with '/' */
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    return $scheme.'://'.$abs; /**< absolute URL is ready! */
  }

/** 
* \brief Takes home url of some site, returns array of dead links
* @param $home_url the home URL where the dead link finding starts. Should be landing page URL
* @return array - of deadlinks and info [[deadLink, whereFound, whyDead], [deadLink, whereFound, whyDead], ...]
* The functionn performs a breath-first search trough the provided website
* FInds all links in current URL, tries to download them and if it can't
* adds to deadliink result array
*/
  function bfs($home_url) 
  {
    $Q = new SplQueue(); //Queue for storing current urls
    $Qfrom = new SplQueue(); //Queue for storing url where current url was found
    $set = array($home_url => 1); 
    $result = array();
    $Q->enqueue($home_url);
    $Qfrom->enqueue($home_url);

    while(!$Q->isEmpty())
    {
      $cur = $Q->dequeue();
      $from = $Qfrom->dequeue();
      echo $cur . "\n";

      try
      {
        $html = file_get_contents($cur);
      }
      catch(Exception $e)
      {
        $html = false;
      }

      if($html == false  || !strpos($http_response_header[0], 'OK')) /**<First check if link is dead. */
      {
        $httpHeader = 'No header, failed to fetch';

        if(isset($http_response_header))
          $httpHeader = $http_response_header[0];
        
        if(strpos($httpHeader, 'OK'))
          continue;
        
        array_push($result, array($cur, $from, $httpHeader));

        continue;
      }

      if(substr($cur,0,strlen($home_url)) != $home_url) /**<Checks if prefix of current link matches base URL. If not doesn't check it */
        continue;
      
      $regex = '#(href|src)="[^"]+"#'; 
      preg_match_all($regex, $html, $urls);

      $urls[0] = str_replace ( 'href="', '', $urls[0]);
      $urls[0] = str_replace ( 'src="', '', $urls[0]);

      foreach ($urls[0] as &$url)
      {
        $url = substr($url, 0, -1); /**< removes trailing "*/

        $url = preg_replace('#\?.+#', '', $url); /**<removes querry string */

        $url = rel2abs($url, $cur);
        
        if(!array_key_exists($url, $set))
        {
          $Q->enqueue($url);
          $Qfrom->enqueue($cur);
          $set[$url]=1;
        }
      }
    }

    return $result;
  }

  if ($argv[1])
    $websiteUrl = (string)$argv[1];
  else
    $websiteUrl = "https://dead-links.freesite.host/";

  try {
    echo "\nChecking for deadlinks in " . $websiteUrl . ")\n";

    $deadLinkai = bfs($websiteUrl);

    $separator = ',';
    $output = '"Deadlink url"' . $separator . '"found in"' . '"response"' . "\n";
    foreach ($deadLinkai as &$dead)
    {
      $output .= "\"" . $dead[0] . '"' . $separator . '"' . $dead[1] . '"' . $separator . '"' . $dead[2] . '"' . "\n";
    }
    file_put_contents ( "output.csv" , $output);
    echo "\nCompleted, results saved in \"output.csv\"\n";
  } catch (\Throwable $th) {
    echo "ERROR: Invalid url format, quitting...\n";
  }
?>
