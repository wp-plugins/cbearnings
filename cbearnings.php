<?php
ini_set('dispay_errors',1);
/**
Plugin Name: CBEARNINGS
Plugin URI: http://www.ifyouknowit.com/
Description: This plugin will provide clickbank database. To help click bank affilates in their effort to promote clickbank products.   
Author: ifyouknowit.com
Version: 1.0
Author URI: http://www.ifyouknowit.com/
*/

class cbearnings{
      
      private static $instance;
        
      static function GetInstance()
      {
          
          if (!isset(self::$instance))
          {
              self::$instance = new self();
          }
          return self::$instance;
      }


	public function PluginMenu()
	{
				add_menu_page(
		                                'CB Earnings', 
		                                'CB Earnings', 
		                                'manage_options',
		                                __FILE__, 
		                                array($this,'RenderPage')                                         
		                                );

		  	add_submenu_page(__FILE__, 'Import data', 'Import data', 'manage_options', __FILE__.'/importdata',array($this,'processfeed'));
		
		/*$options = get_option('plugin_options');

		if($options['accept_input_forpost']){
				add_action('add_meta_boxes', array($this,'add_meta_box'));
				add_action('save_post', array($this, 'save' ));
		}*/

	}

      public function InitPlugin()
      {
	  
          add_action('admin_menu',array($this,'PluginMenu'));
      }

	public function RenderPage(){
	 ?>	
	  <div class="wrap_cbearnings">
		<h1>Help</h1>
		<div>Short code is : [CB_EARNINGS]</div>
		<div>put this short code in any post and/or page : [CB_EARNINGS]</div>  
	  </div>
	  
	<?php }	 
		
      


        public function processfeed(){
		 set_time_limit(0);
		 //$url='https://accounts.clickbank.com/feeds/marketplace_feed_v2.xml.zip';
	 	 //$extractPath='/var/www/wordpress/xmlfeed/';

		$options = get_option('plugin_options_cb');

		$url=$options['cb_url'];
		$extractPath=$options['cb_feed_path'];

		 $xmlfile = 'marketplace_feed_v2.xml';
		 $this->getfeeds($url,$extractPath);
		 $this->savefeedsindb($xmlfile,$extractPath);
	}  	

	public function savefeedsindb($xmlfile,$filepath){
	global $wpdb;	
	
	//include('mydb.php');
	$i=0;

	if($xmlfile==''){
	 $xmlfile = 'marketplace_feed_v2.xml';
	}	

if (file_exists($filepath.$xmlfile)) {
    $xml = simplexml_load_file($filepath.$xmlfile,'SimpleXMLElement',LIBXML_NOCDATA);
    
    foreach($xml->Category as $catobj){
	    // print $catobj->Name;
	    $sql="select * from ".$wpdb->prefix.'cbcategory'." where catname='$catobj->Name'";
	    $rs = $wpdb->get_results($sql);
	    $count=$wpdb->num_rows;
	    
	    
	    if($count<1){
		$insert_sql="insert into ".$wpdb->prefix."cbcategory(catname) values('$catobj->Name')";
		$wpdb->query($insert_sql);
		$id=$wpdb->insert_id;
	    } else {
		$sel="select slid from ".$wpdb->prefix."cbcategory where catname='$catobj->Name'";
		$ids = $wpdb->get_col("select slid from ".$wpdb->prefix ."cbcategory where catname='$catobj->Name' ORDER BY slid DESC LIMIT 0 , 1" );				
		$id=$ids[0];
	    }

	
	//return;
	   
      
	//<EarnedPerSale>75.5686</EarnedPerSale>
	//<PercentPerSale>60.0</PercentPerSale>

	      foreach($catobj->Site as $siteobj){
		      
		    $sql_check="select * from ".$wpdb->prefix."cbtable where id='$siteobj->Id'";
		    $rs_check = $wpdb->get_results($sql_check);
		    $num=$wpdb->num_rows;

			if($siteobj->PercentPerSale==''){
				$siteobj->PercentPerSale='0.00';	
			}

			if($siteobj->EarnedPerSale==''){
				$siteobj->EarnedPerSale='0.00';	
			}
		      
		      if($num>=1){
		      $sql_e="update ".$wpdb->prefix. "cbtable set id='$siteobj->Id',catid=$id,title='$siteobj->Title',description='$siteobj->Description',epersale=$siteobj->EarnedPerSale,ppersale=$siteobj->PercentPerSale where id='$siteobj->Id'";
		      } else {
		       $sql_e="insert into ".$wpdb->prefix ."cbtable(id,catid,title,description,epersale,ppersale) values('$siteobj->Id',$id,'$siteobj->Title','$siteobj->Description',$siteobj->EarnedPerSale,$siteobj->PercentPerSale)";
		      }
		      // print $sql_e;	
		      $wpdb->query($sql_e);
		      $i++;	
	      }
	      	
    }
 	print '<div>Done and imported..'.$i.'</div>';   
	} else {
	    exit('Failed to open test.xml.');
	}

  }


 public function getfeeds($url='',$extractPath=''){
	
	if($url==''){
	  $url = "http://www.ifyouknowit.com/myplugins/mapworks.zip";
	}
	

	if($extractPath==''){
		$extractPath = "/var/www/wordpress/xmlfeed/";
	}

	$zipFile = $extractPath."zipfile.zip"; // Local Zip File Path

	$filedata = file_get_contents($url);
	

	// print $zipFile;
	file_put_contents($zipFile,$filedata);

	$zip = new ZipArchive;

	if($zip->open($zipFile) != "true"){
	 echo "Error :- Unable to open the Zip File";
	}
	 
	$zip->extractTo($extractPath);
	$zip->close();

 } 

    
}

global $cbearnings_db_version;
$cbearnings_db_version = '1.0';

function cbearnings_install() {
	global $wpdb;
	global $cbearnings_db_version;

	$table_name = $wpdb->prefix . 'cbtable';
	$table_name_cat = $wpdb->prefix . 'cbcategory';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		slid int(19) NOT NULL AUTO_INCREMENT,
		id varchar(255),
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		catid integer(19),
		title text NOT NULL,
		description text NOT NULL,
		epersale decimal(10,5),
		ppersale decimal(10,5),		
		UNIQUE KEY slid (slid)
	) $charset_collate;";

	$sql_cat = "CREATE TABLE $table_name_cat (
		slid int(19) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		catname varchar(255),		
		UNIQUE KEY id (slid)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
	dbDelta($sql_cat);

	add_option('cbearnings_db_version', $cbearnings_db_version );



	$installed_ver = get_option("cbearnings_db_version");

	if ( $installed_ver != $cbearnings_db_version ) {

		$table_name = $wpdb->prefix . 'cbtable';

		$sql = "CREATE TABLE $table_name (
		slid int(19) NOT NULL AUTO_INCREMENT,
		id varchar(255),
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		catid integer(19),
		title text NOT NULL,
		description text NOT NULL,
		epersale decimal(10,5),
		ppersale decimal(10,5),		
		UNIQUE KEY slid (slid)
		);";

	$sql_cat = "CREATE TABLE $table_name_cat (
		slid int(19) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		catname varchar(255),		
		UNIQUE KEY id (slid)
	) $charset_collate;";


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
		dbDelta($sql_cat);

		update_option("cbearnings_db_version",$cbearnings_db_version );
	}

}


register_activation_hook( __FILE__, 'cbearnings_install' );
	  



//add_shortcode('CBEARNINGS','cbearnings');

$myplugin=cbearnings::GetInstance();
$myplugin->InitPlugin();

function cbearnings_short($atts){

$options = get_option('plugin_options_cb');
$cb_id = $options['cb_id'];

global $wpdb;

$pagenum = isset($_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
$limit = 10;
$offset = ( $pagenum - 1 ) * $limit;

$total = $wpdb->get_var("SELECT COUNT(slid) FROM {$wpdb->prefix}cbtable");
$num_of_pages = ceil($total/$limit );	

$atts = shortcode_atts(array(
		'cbtext' => ''
	),$atts,'CB_EARNINGS');

$cbsearch=$atts['cbtext'];

ob_start();


$search=$cbsearch;


if($search!=''){
$sql1="select * from ".$wpdb->prefix."cbtable where (title like '%".$search."%' or description like '%".$search."%') limit $offset,$limit";
} else {
$sql1="select * from ".$wpdb->prefix."cbtable limit $offset,$limit";
}

$page_links = paginate_links(array(
	'base' => add_query_arg('pagenum', '%#%'),
	'format' => '',
	'prev_text'          => __('« Previous'),
	'next_text'          => __('Next »'),
	 'mid_size'           => 5,
	'total' => $num_of_pages,
	'current' => $pagenum
) );

	$rows=$wpdb->get_results($sql1,ARRAY_A);




        foreach($rows as $myrow){
	$str="http://".$cb_id.".".$myrow['id'].".hop.clickbank.net/";
        print '<div><h3>'.str_replace('&','and',$myrow['title']).'</h3></div>
        <div class="desc"><font color=green>'.str_replace('&','and',$myrow['description']).'</font></div>
        <div class="mylink"><a href="'.$str.'">'.'Buy it'.'</a></div>';      
   	}

if ( $page_links ) {
 echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
}


$out2 = ob_get_contents();
ob_end_clean();
return $out2; 
}



add_filter('the_content',"shortcode_tags");
add_shortcode('CB_EARNINGS','cbearnings_short');


add_action('admin_menu', 'plugin_admin_add_page_cb');

function plugin_admin_add_page_cb() {
	add_options_page('CBEARNINGS Settings', 'CBEARNINGS', 'manage_options', 'mplugin', 'plugin_options_pagecb');
}


function plugin_options_pagecb() {

$options = get_option('plugin_options_cb');
$cb_feed_path = $options['cb_feed_path'];


if(!file_exists($cb_feed_path) && !is_dir($cb_feed_path)){
    mkdir($cb_feed_path,0777);         
} else {
$p=getFilePermission($cb_feed_path);

if($p==='777'){
} else {
 chmod($cb_feed_path,0777);         
}

}

?>
<div>
<form action="options.php" method="post">
<?php settings_fields('plugin_options_cb'); ?>
<?php do_settings_sections('mplugin'); ?> 
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>
<?php
}


function getFilePermission($file){
        $length = strlen(decoct(fileperms($file)))-3;
        return substr(decoct(fileperms($file)),$length);
}


add_action('admin_init', 'plugin_admin_initcb');

function plugin_admin_initcb(){
register_setting('plugin_options_cb','plugin_options_cb','plugin_options_cb_validate');

add_settings_section('plugin_main', 'CBEARNINGS Settings', 'plugin_section_text_cb', 'mplugin');

add_settings_field('plugin_check_one_cb', 'Click bank url', 'plugin_setting_check_one_cb', 'mplugin', 'plugin_main');
add_settings_field('plugin_check_two_cb', 'XML feed Path', 'plugin_setting_check_two_cb', 'mplugin', 'plugin_main');
add_settings_field('plugin_check_four_cb', 'Click bank id', 'plugin_setting_check_four_cb', 'mplugin', 'plugin_main');
}

 
function plugin_options_cb_validate($input) {
return $input;
}



function plugin_setting_check_one_cb() {
	$options = get_option('plugin_options_cb');

	if($options['cb_url']){
	 $st=$options['cb_url'];
	} else {
	 $st='https://accounts.clickbank.com/feeds/marketplace_feed_v2.xml.zip';
	}

	echo "<input id='plugin_check_one_cb' name='plugin_options_cb[cb_url]' size='40' type='text' value='".$st."'>";

} 

function plugin_setting_check_two_cb() {
	$options = get_option('plugin_options_cb');
	if($options['cb_feed_path']){
	 $st=$options['cb_feed_path'];
	} else {
	 $st='/var/www/html/wordpress/xmlfeed/';
	}
	echo "<input id='plugin_check_two' {$st} name='plugin_options_cb[cb_feed_path]' size='40' type='text' value='".$st."'/>";
}

function plugin_setting_check_four_cb() {
	$options = get_option('plugin_options_cb');
	if($options['cb_id']){
	 $st=$options['cb_id'];
	} else {
	 $st='pmohanty';
	}
	echo "<input id='plugin_check_two' {$st} name='plugin_options_cb[cb_id]' size='40' type='text' value='".$st."'/>";
}

function plugin_section_text_cb() {
	echo '<p>Set Url and Feed Path</p>';
}


function shortcodephp($args, $content=""){
			error_reporting(E_ALL);
			ini_set("display_errors","1");

					$content =(htmlspecialchars($content,ENT_QUOTES));
					$content = str_replace("&amp;#8217;","'",$content);
					$content = str_replace("&amp;#8216;","'",$content);
					$content = str_replace("&amp;#8242;","'",$content);
					$content = str_replace("&amp;#8220;","\"",$content);
					$content = str_replace("&amp;#8221;","\"",$content);
					$content = str_replace("&amp;#8243;","\"",$content);
					$content = str_replace("&amp;#039;","'",$content);
					$content = str_replace("&#039;","'",$content);
					$content = str_replace("&amp;#038;","&",$content);
					$content = str_replace("&amp;lt;br /&amp;gt;"," ", $content);
					$content = htmlspecialchars_decode($content);
					$content = str_replace("<br />"," ",$content);
					$content = str_replace("<p>"," ",$content);
					$content = str_replace("</p>"," ",$content);
					$content = str_replace("[br/]","<br/>",$content);
					$content = str_replace("\\[","&#91;",$content);
					$content = str_replace("\\]","&#93;",$content);
					$content = str_replace("[","<",$content);
					$content = str_replace("]",">",$content);
					$content = str_replace("&#91;",'[',$content);
					$content = str_replace("&#93;",']',$content);
					$content = str_replace("&gt;",'>',$content);
					$content = str_replace("&lt;",'<',$content);
			ob_start();
			eval($content);
			return ob_get_clean();
}

function shortcode_tags($content){
				error_reporting(E_ALL);
				ini_set("display_errors","1");
			
					//remove_shortcode("PHP");
					//remove_shortcode("php");
					
					$content = str_ireplace("[php]","<?php ",$content);
					$content = str_ireplace("[/php]"," ?>",$content);					
					
					$content =(htmlspecialchars($content,ENT_QUOTES));
					$content = str_replace("&amp;#8217;","'",$content);
					$content = str_replace("&amp;#8216;","'",$content);
					$content = str_replace("&amp;#8242;","'",$content);
					$content = str_replace("&amp;#8220;","\"",$content);
					$content = str_replace("&amp;#8221;","\"",$content);
					$content = str_replace("&amp;#8243;","\"",$content);
					$content = str_replace("&amp;#039;","'",$content);
					$content = str_replace("&#039;","'",$content);
					$content = str_replace("&amp;#038;","&",$content);
					$content = str_replace("&amp;lt;br /&amp;gt;"," ", $content);
					$content = htmlspecialchars_decode($content);					
					$content = str_replace("&#91;",'[',$content);
					$content = str_replace("&#93;",']',$content);
					$content = str_replace("&gt;",'>',$content);
					$content = str_replace("&lt;",'<',$content);

					ob_start();
					eval('?>'.$content);
					$return = ob_get_clean();
					return $return;
				
				
}


add_shortcode('php',"shortcodephp");
add_shortcode('PHP',"shortcodephp");  
 
?>
