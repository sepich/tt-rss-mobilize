<?php
class mobilize extends Plugin {
	private $host;

	function init($host) {
		$this->host = $host;
		$host->add_hook($host::HOOK_HOTKEY_MAP, $this);
    $host->add_hook($host::HOOK_HOTKEY_INFO, $this);
    $host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
    $host->add_hook($host::HOOK_PREFS_EDIT_FEED, $this);
    $host->add_hook($host::HOOK_PREFS_SAVE_FEED, $this);
    
    //check first run
		$result=db_query( 'SELECT 1 FROM plugin_mobilize_mobilizers' );
		if (db_num_rows($result) == 0) {
      db_query("
        CREATE TABLE IF NOT EXISTS `plugin_mobilize_mobilizers` (
          `id` int(11) NOT NULL,
          `description` varchar(255) NOT NULL,
          `url` varchar(1000) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
      ");	
      
      db_query(
        "INSERT INTO `plugin_mobilize_mobilizers` (`id`, `description`, `url`) VALUES
        (0, 'Readability', 'http://www.readability.com/m?url=%s'),
        (1, 'Instapaper', 'http://www.instapaper.com/m?u=%s'),
        (2, 'Google Mobilizer', 'http://www.google.com/gwt/x?u=%s'),
        (3, 'Original Stripped', 'http://strip=%s'),
        (4, 'Original', '%s');
      ");
      
      db_query("CREATE TABLE IF NOT EXISTS `plugin_mobilize_feeds` (
        `id` int(11) NOT NULL,
        `owner_uid` int(11) NOT NULL,
        `mobilizer_id` int(11) NOT NULL,
        PRIMARY KEY (`id`,`owner_uid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
      ");
		}


	}

	function about() {
		return array(1.0,
			"Display original article content stripped by Mobilizer service",
			"sepa", false, "http://blog.sepa.spb.ru/2013/05/article-mobilizer-plugin-for-tt-rss.html");
	}

	function api_version() {
		return 2;
	}

	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/init.js");
	}

  function hook_hotkey_map($hotkeys) {
  	$hotkeys['v'] = "mobilize";
  	return $hotkeys;
 	}

	function hook_hotkey_info($hotkeys) {
    $offset = 1 + array_search('open_in_new_window', array_keys($hotkeys[__('Article')]));
    $hotkeys[__('Article')] =
        array_slice($hotkeys[__('Article')], 0, $offset, true) +
        array('mobilize' => __('Load mobilized version')) +
        array_slice($hotkeys[__('Article')], $offset, NULL, true);

    return $hotkeys;
	}

  function hook_article_button($line) {
		$id = $line["id"];

		$rv = "<img src=\"plugins/mobilize/but.png\"
			class='tagsPic' style=\"cursor : pointer\"
			onclick=\"mobilizeArticle($id)\"
			title='".__('Toggle mobilize original')."'>";

		return $rv;
	}

	function hook_prefs_edit_feed($feed_id) {
		print "<div class=\"dlgSec\">".__("Feed content")."</div>";
		print "<div class=\"dlgSecCont\">";


		$contPref   = db_query("SELECT mobilizer_id from plugin_mobilize_feeds where id = '$feed_id' AND
				owner_uid = " . $_SESSION["uid"]);
		$mobilizer_id=0;
		if (db_num_rows($contPref) != 0) {
			$mobilizer_id = db_fetch_result($contPref, 0, "mobilizer_id");
		}
		
		$contResult = db_query("SELECT id,description from plugin_mobilize_mobilizers order by id");

		while ($line = db_fetch_assoc($contResult)) {
			$mobilizer_ids[$line["id"]]=$line["description"];
		}

		print_select_hash("mobilizer_id", $mobilizer_id, $mobilizer_ids, 'dojoType="dijit.form.Select"');
		print "</div>";
	}		


	function hook_prefs_save_feed($feed_id) {
		$mobilizer_id = (int) db_escape_string($_POST["mobilizer_id"]);
		$result = db_query("DELETE FROM plugin_mobilize_feeds 
			WHERE id = '$feed_id' AND owner_uid = " . $_SESSION["uid"]);
	
		$result = db_query("INSERT INTO plugin_mobilize_feeds
			(id,owner_uid,mobilizer_id)
			VALUES ('$feed_id', '".$_SESSION["uid"]."', '$mobilizer_id')");
	}

  //to be called from js  
	function getUrl() {
		$id = db_escape_string($_REQUEST['id']);

    //get feed url
		$result1 = db_query("SELECT link
			FROM ttrss_entries, ttrss_user_entries
			WHERE id = '$id' AND ref_id = id AND owner_uid = " .$_SESSION['uid']);
		$url = "";
		if (db_num_rows($result1) != 0) {
			$url = db_fetch_result($result1, 0, "link");
		}

		//search for feed mobilizer
		$result2 = db_query("SELECT url
			FROM  ttrss_user_entries ue, plugin_mobilize_feeds pf, plugin_mobilize_mobilizers pm
			WHERE ue.ref_id = '$id' and ue.owner_uid = " . $_SESSION['uid'] ." 
			and ue.feed_id = pf.id 
			and pf.owner_uid = ue.owner_uid
			and pf.mobilizer_id = pm.id");

  	//no mobilizer set for this feed, select default	
		if (!db_num_rows($result2)) {	
    	$result2 = db_query("SELECT url	FROM  plugin_mobilize_mobilizers WHERE id = '0'");	
		}
		
		$mobilizer_url = $url;

		if (db_num_rows($result2) != 0) {
			$mobilizer_url = db_fetch_result($result2, 0, "url");
			if ($mobilizer_url <> "") { # we got an configured url for the feed, lets do search and replace
				$mobilizer_url=str_replace("%s",$url,$mobilizer_url);
			} else {
				$mobilizer_url = $url;
			}
		}

		print json_encode(array("url" => $mobilizer_url, "id" => $id));
	}

}
?>
