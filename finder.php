<?php

  //relative linka transformuoja i absoliutu
  function rel2abs($rel, $base)
  {
      /* return if already absolute URL */
      if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

      /* empty section link, can be ignored*/
      if($rel == '#')return $base;

      /* queries and anchors */
      if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

      /* parse base URL and convert to local variables: $scheme, $host, $path */
      extract(parse_url($base));

      /* remove non-directory element from path */
      $path = preg_replace('#/[^/]*$#', '', $path);

      /* destroy path if relative url points to root */
      if ($rel[0] == '/') $path = '';

      /* dirty absolute URL */
      $abs = "$host$path/$rel";

      /* replace '//' or '/./' or '/foo/../' with '/' */
      $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
      for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

      /* absolute URL is ready! */
      return $scheme.'://'.$abs;
  }


  // Takes home url of some site, returns array of dead links
  //returns array [[deadLink, whereFound, whyDead], [deadLink, whereFound, whyDead], ...]

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

        //linkas mires, ar isvis neegzistuoja domain
        if($html == false  || !strpos($http_response_header[0], 'OK')) 
        {
          $httpHeader = 'No header, failed to fetch';

          if(isset($http_response_header))
            $httpHeader = $http_response_header[0];
          
          if(strpos($httpHeader, 'OK'))
            continue;
          
          array_push($result, array($cur, $from, $httpHeader));

          continue;
        }

        //jei prefixas neatitinka home/root page tai tame puslapyje linku neieskom
        if(substr($cur,0,strlen($home_url)) != $home_url) 
          continue;
        
        $regex = '#(href|src)="[^"]+"#'; 
        preg_match_all($regex, $html, $urls);

        $urls[0] = str_replace ( 'href="', '', $urls[0]);
        $urls[0] = str_replace ( 'src="', '', $urls[0]);

        foreach ($urls[0] as &$url)
        {
          //pasalinam trailing kabute "
          $url = substr($url, 0, -1);

          //pasalinam query string
          $url = preg_replace('#\?.+#', '', $url);

          //gaunam absoliutu linka
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

  $deadLinkai = bfs("https://dead-links.freesite.host/"); 

  //echo "\n\n\nOUTPUT:\n\n\n";
  //var_dump($deadLinkai);
  $output = '';
  foreach ($deadLinkai as &$dead)
  {
    $output .= $dead[0] . ' ' . $dead[1] . ' '  . $dead[2] . "\n";
  }
  file_put_contents ( "output.txt" , $output);

?>