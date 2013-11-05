<?
$g=$_SERVER['QUERY_STRING'];

//strip headers and footers
//instapaper
if( stripos($g,'instapaper.com/m?') ){
  $htm=file_get_contents($g);
  $DOM = new DOMDocument;
  $DOM->loadHTML($htm);
  $bar = $DOM->getElementById('controlbar_container');
  if($bar){
    $parent = $bar->parentNode;
    $parent->removeChild($bar);
  }
  $htm=$DOM->saveHTML();
}
//google mobilizer
elseif( stripos($g,'google.com/gwt') ){
  $htm=file_get_contents($g);
  $DOM = new DOMDocument;
  $DOM->loadHTML($htm);
  $divs = $DOM->getElementsByTagName('body')->item(0)->childNodes->item(0)->childNodes;

  $bar=$divs->item(0);
  if($bar){
    $parent = $bar->parentNode;
    $parent->removeChild($bar);
  }
  $foot=$divs->item(1);
  if($foot) $parent->removeChild($foot);

  $htm=$DOM->saveHTML();
}
elseif( stripos($g,'.readability.com/m?') ){
  $htm=file_get_contents($g);
  $DOM = new DOMDocument;
  $DOM->loadHTML($htm);
  $bar = $DOM->getElementById('rdb-header');
  $parent = $bar->parentNode;
  $parent->removeChild($bar);
  $bar = $DOM->getElementById('call-to-action');
  if($bar){
    $parent = $bar->parentNode;
    $parent->removeChild($bar);
  }
  $bar = $DOM->getElementById('rdb-article-original-url');
  if($bar){
    $parent = $bar->parentNode;
    $parent->removeChild($bar);   
  }
  $bar = $DOM->getElementsByTagName('footer')->item(0);
  if($bar){
    $parent = $bar->parentNode;
    $parent->removeChild($bar);
  }  
  $htm=$DOM->saveHTML();  
}
//original stripped
elseif( stripos($g,'ttp://strip=') ) {
  //detect charset
  list($a,$g)=explode("=",$g);
  $htm=file_get_contents($g);
}
//redirect to original
else {  
  header("Location: $g");
  die();   
}

header('Content-Type: text/html; charset=utf-8',true);
die($htm);

?>