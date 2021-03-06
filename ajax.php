<?php
error_reporting(0);
class StalkErr extends Exception {}
define("ERR_URI", "Invalid URL or Profile: Are you sure it's a valid link: \n\nhttps://www.facebook.com/username\nhttp://m.facebook.com/profile.php?id=123");
define("ERR_PAGE","The link you provided is of a page not a Facebook \"profile\"\n\nPlease try again with a valid profile");
define("ERR_UPSTREAM_NORESPONSE","Upstream didn't respond, please refresh the page and try again!");
define("ERR_BLACKLIST","Sorry, that's not possible at the moment!");
header("Content-Type: application/json");
header("X-FRAME-OPTIONS: SAMEORIGIN");
function getId($id){
	if(!preg_match("/^(http|https)/i", $id)) $id = "https://".$id;
	$hosts = array("www.facebook.com","zero.facebook.com","iphone.facebook.com","0.facebook.com","facebook.com","touch.facebook.com","m.facebook.com","mbasic.facebook.com");
	$id = parse_url($id);
	if(!in_array($id["host"],$hosts)) return -1;
	parse_str($id['query']);
	if(!is_numeric($id)) $id  = substr($id['path'],1);
	$x = json_decode(file_get_contents("http://graph.facebook.com/".$id));
    if(!is_numeric($x->id)) return -2;
    return $x;
}
function main(){
	try {
		$blacklist = array("prakharprasad"); //backlist usernames, CSV formatted
		$id = @getId($_GET['id']);
		if(intval($id->id) <= 0) throw new StalkErr(ERR_URI);
		if($id->likes) throw new StalkErr(ERR_PAGE);
		if(in_array($id->username,$blacklist)) throw new StalkErr(ERR_BLACKLIST);
		$url = get_headers("http://graph.facebook.com/".$id->id."/picture?type=large",1)["Location"];
		if(!$url)  throw new StalkErr(ERR_UPSTREAM_NORESPONSE);
		$image = file_get_contents(preg_replace("/[0-9]{3}x[0-9]{3}/i","720x720",$url));
		if(!@getimagesizefromstring($image)) $image = file_get_contents($url); //fallback to Graph API maximum, when resizing fails
		echo json_encode(array("hash"=> htmlspecialchars($id->username,ENT_QUOTES),"title" => htmlspecialchars($id->name,ENT_QUOTES)." | ".ucfirst(htmlspecialchars($id->gender,ENT_QUOTES)),
			 "data"=>"data:image/jpg;base64,".base64_encode($image)));
	    }
	catch(Exception $e)
	 {
		echo json_encode(array("error" => $e->getMessage()));
	 } }
main(); //Like a C programmer ;)
?>