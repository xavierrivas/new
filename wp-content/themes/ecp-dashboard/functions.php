<?php
//error_reporting(1);
//ini_set('display_errors', 1);

$themename = "BlogDesignStudio_Idioglossia";
$shortname = "Idioglossia";


/*
 * Declare constants - used through the whole framework
 */
define('IDG_ROOT', TEMPLATEPATH);
define('IDG_THEMEASSETS', TEMPLATEPATH . '/lib');
define('IDG_CLASS_PATH', TEMPLATEPATH . '/lib/classes/');
define("IDG_POST_CONF_FILE", TEMPLATEPATH."/lib/models/post.xml");
define("IDG_PAGE_CONF_FILE", TEMPLATEPATH."/lib/models/page.xml");
define("IDG_THEME_CONF_FILE", TEMPLATEPATH."/lib/models/theme.xml");
define("IDG_WIDGETS_CONF_FILE", TEMPLATEPATH."/lib/models/widgets.xml");
define("IDG_USERS_CONF_FILE", TEMPLATEPATH."/lib/models/users.xml");
define("IDG_CREATE_USERS_CONF_FILE", TEMPLATEPATH."/lib/models/create_users.xml");
define("IDG_PLUGINS_PATH", TEMPLATEPATH."/lib/plugins/");

define("IDGL_THEME_URL", get_bloginfo('template_directory'));

/*
 * Include base classes
 */
require_once(IDG_CLASS_PATH."class.Util.php");
require_once(IDG_CLASS_PATH."class.Config.php");
require_once(IDG_CLASS_PATH."class.PostOptionManages.php");
require_once(IDG_CLASS_PATH."class.PostType.php");
require_once(IDG_CLASS_PATH."class.CustomPostList.php");
require_once(IDG_CLASS_PATH."class.ThemePages.php");
require_once(IDG_CLASS_PATH."class.IDGL_Users.php");
require_once(IDG_CLASS_PATH."class.WidgetAreas.php");
require_once(IDG_CLASS_PATH."class.Widgetizer.php");
require_once(IDG_CLASS_PATH."class.Sidebar.php");
require_once(IDG_CLASS_PATH."class.IDGL_DataGrid.php");
require_once(IDG_CLASS_PATH."class.BFormValidator.php");


/*
 * Register scripts / maybe these should go in the admin head
 */
wp_enqueue_script('jquery');
wp_register_script('jquery-ui', IDGL_THEME_URL . '/lib/js/jquery-ui-1.8.24.custom.min.js');
wp_enqueue_script('jquery-ui');
wp_register_script('main', IDGL_THEME_URL . '/lib/js/main.js');
wp_enqueue_script('main');

/*
 * Print admin head
 */
add_action('admin_head','IDGL_Admin_head');
function IDGL_Admin_head() {
	global $IDGL_model;
	$uplData=wp_upload_dir();
	echo '<link type="text/css" rel="stylesheet" href="'.IDGL_THEME_URL.'/lib/js/colorpicker/css/colorpicker.css" />' . "\n";
	
	echo '<link type="text/css" rel="stylesheet" href="'.IDGL_THEME_URL.'/lib/css/ui-lightness/jquery-ui-1.8.24.custom.css" />' . "\n";
	echo '<link type="text/css" rel="stylesheet" href="'.IDGL_THEME_URL.'/lib/css/styles.css" />' . "\n";
	echo '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script> ';
	echo "<script type='text/javascript'>
			var imagesPath='".IDGL_THEME_URL."/lib/images/';
			var adminAjax='".get_bloginfo('url')."/wp-admin/admin-ajax.php';
			var uploadPath='".$uplData["url"]."/';
			var selectedModel='".$IDGL_model."';
			var browserPath='".IDGL_THEME_URL."/lib/plugins/ecp_self_tests/browser/ajaxHandler.php';
			var browserDir='".IDGL_THEME_URL."/lib/plugins/ecp_self_tests/browser/';
		</script>";
	?>
	<script type="text/javascript">
		jQuery(function($){
			//$("#toplevel_page_student-list ul:nth-child(n), #toplevel_page_student-list ul:nth-child(n-1)").hide();
			var n = $("#toplevel_page_student-list ul li").length;
			$("#toplevel_page_student-list ul :nth-child(" + n + ")").hide();	
			$("#toplevel_page_student-list ul :nth-child(" + (n - 1) + ")").hide();
		});
	</script>
	<?php
		if (function_exists('wp_tiny_mce')) wp_tiny_mce();
		wp_admin_css();

}

add_action('wp_head','IDGL_add_scripts_front');
function IDGL_add_scripts_front(){
	echo "<script type='text/javascript'>
			var imagesPath='".IDGL_THEME_URL."/lib/images/';
			var adminAjax='".get_bloginfo('url')."/wp-admin/admin-ajax.php';
		</script>";
}

if(class_exists("IDGL_PostOptionManages")){
	add_action('save_post', 'IDGL_PostOptionManages::IDGL_savePostData');
}
if(class_exists("IDGL_ThemePages")){
	add_action('admin_menu', 'IDGL_ThemePages::IDGL_addPages');
}
if(class_exists("IDGL_PostOptionManages")){
	add_action('admin_menu', 'IDGL_PostOptionManages::IDGL_addPostPanels');
}
if(class_exists("PostType")){
	add_action('init', 'PostType::register');
}
if(class_exists("IDGL_WidgetAreas")){
	add_action( 'widgets_init', 'IDGL_WidgetAreas::register_widget_areas' );
}
if(class_exists("IDGL_Widgetizer")){
	add_action( 'widgets_init', 'IDGL_Widgetizer::widgetize' );
}

add_action('admin_init', 'IDGL_init' );
function IDGL_init(){
   	IDGL_ThemePages::IDGL_register_setting();
	//register_setting('IDGL-settings-group', 'IDGL_elem');
}

/*
 * users and user types
 */
if(class_exists("IDGL_Users")){
	add_action( 'show_user_profile', 'IDGL_show_add_user_fields' );
	add_action( 'edit_user_profile', 'IDGL_show_add_user_fields' );
	add_action( 'personal_options_update', 'IDGL_save_add_user_fields' );
	add_action( 'edit_user_profile_update', 'IDGL_save_add_user_fields' );
}
function IDGL_show_add_user_fields( $user ) {
	IDGL_Users::IDGL_addUserPanels($user);
}
function IDGL_save_add_user_fields( $user ) { 
	if ( !current_user_can( 'edit_user', $user_id ) ) return false;
	IDGL_Users::IDGL_saveUserMeta($user);
}



/*
 * Upload handler - used by the image uploader
 */
add_action('wp_ajax_imageUpload', 'imageUpload' );
function imageUpload(){
	//echo "***";
	$uplData=wp_upload_dir();
	include "lib/classes/class.Image.php";
	$tempFile = $_FILES['Filedata']['tmp_name'];
	print_r($_GET);
	if(isset($_GET["newFileName"])){
		$targetFile =str_replace(" ","_",$uplData["path"]."/".$_GET["newFileName"]);
		$targetThumb =str_replace(" ","_",$uplData["path"]."/thumb_".$_GET["newFileName"]);
	}else{
		$targetFile =str_replace(" ","_",$uplData["path"]."/".$_FILES['Filedata']['name']);
		$targetThumb =str_replace(" ","_",$uplData["path"]."/thumb_".$_FILES['Filedata']['name']);
	}
	echo $targetFile."\n";
	echo $targetThumb."\n";
	move_uploaded_file($tempFile,$targetFile);
	if(isset($_GET["w"])){
		$sizes=explode("|",$_GET["w"]);
		if($sizes[2]!=null){
			WPACImage::resizeTo($sizes[2],$sizes[3],$targetFile,$targetThumb);
		}
		WPACImage::resizeTo($sizes[0],$sizes[1],$targetFile);
	}
	die();
}

/* interfaceFunctions */
/*
 * hasPostMeta::Get a value defined in the in the "wp_postmeta" table
 * 		$pid - id of the post/page
 * 		$key - name of the key	
 * 		$default - default value to be returned if no result has been retrieved
 */
function getPostMeta($pid,$key,$default=""){
	$meta=get_post_meta($pid, "_IDGL_elem_".$key, true);
	if($meta!=""){
	if(is_array($meta)){
			return $meta;
		}else{
			return stripcslashes($meta);
		}
	}else{
		return $default;
	}
}
/*
 * hasPostMeta::Checks if a certain key / value exists in the "wp_postmeta" table
 * 		$pid - id of the post/page
 * 		$key - name of the key	
 */
function hasPostMeta($pid, $key){
	$meta=get_post_meta($pid, "_IDGL_elem_".$key, true);
	if($meta!=""){
		return true;
	}
}
/*
 * getThemeMeta::Get a value defined in the "wp_options" table
 * 		$key - name of the key
 * 		$default - default value to be returned if no result has been retrieved
 */
function getThemeMeta($key,$default="",$pageName=""){
	$options = get_option('IDGL_elem'.$pageName);
	if($options[$key]!=""){
		if(is_array($options[$key])){
			return $options[$key];
		}else{
			return stripcslashes($options[$key]);
		}
	}else{
		return $default;
	}
}
$mapCount=0;

/*
 * Creates a map with a marker
 * 		$mapName - the javascript name of the map
 * 		$mapValue - "long,lat,zoom-value" triple
 * 		$w - width of <div> wrapper for the map
 * 		$h - height of <div> wrapper for the map
 */
function renderGoogleMap($mapName,$mapValue,$w=400,$h=300){
	global $mapCount;
	$mapValue=str_replace(array("[","]"),"",$mapValue);
	$mapParams=explode(",",$mapValue);
	echo '<div id="map_canvas-'.$mapCount.'" style="width: '.$w.'px; height: '.$h.'px"></div>';
	echo '<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&key=ABQIAAAACuPnpsfgQtylGTK5oELr8hR0DCSZkES4mAjJ0aPCnwtfcV3XsRSRV4aCAUZF_Yo1zu8OsCxCX7Salw
	"></script> ';
	echo '<script type="text/javascript">
			    var map_'.$mapName.';
				jQuery(function(){
			      if (GBrowserIsCompatible()) {
			        map_'.$mapName.' = new GMap2(document.getElementById("map_canvas-'.$mapCount.'"));
			        map_'.$mapName.'.setCenter(new GLatLng('.$mapParams[0].', '.$mapParams[1].'), '.$mapParams[2].');
			        map_'.$mapName.'.setUIToDefault();
			        //map_'.$mapName.'.openInfoWindow(map_'.$mapName.'.getCenter(),document.createTextNode("Hello, world"));
			        
			      }
			   })
		  </script>';
	$mapCount++;
}

/*
 * Adds a marker to a specific google map
 * 		$mapName - the javascript name of the map - an exsisting map
 * 		$mapValue - "long,lat,zoom-value" triple
 * 		$html - the html content that should appear in the marker's bubble
 */
function addGoogleMapMarker($mapName,$mapValue,$html=""){
	$mapValue=str_replace(array("[","]"),"",$mapValue);
	$mapParams=explode(",",$mapValue);
	echo '<script>
				jQuery(function(){
					var point = new GLatLng('.$mapParams[0].','.$mapParams[1].');
					var marker=new GMarker(point);
			   		map_'.$mapName.'.addOverlay(marker);';
			   	
				if($html!=""){	
			   		echo 'GEvent.addListener(marker, "click", function() {
							    var myHtml = "'.$html.'";
							    map_'.$mapName.'.openInfoWindowHtml(point, myHtml);
							  });';
				}
			echo '})</script>';
}

/*
 * Default rendering for the image gallery node - returnd ul>li>a>img - need to figure out the alt??
 * 		$galArray - an array of comma separated values - paths to images
 */
function renderImageGallery($galArray){
	if(is_array($galArray)){
		foreach($galArray as $image){
			$thumb=str_replace("img_","thumb_img_",$image);
			echo '<li><a href="'.$image.'"><img src="'.$thumb.'" /></a></li>';
		}
	}
}
function getFLVPlayer($video_path,$player_id){
	$imagesPath=IDGL_THEME_URL.'/lib/images/';
	return '<embed height="420" width="640" flashvars="stream='.$video_path.'" wmode="opaque" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="#000000" name="'.$player_id.'" id="'.$player_id.'" style="" src="'.$imagesPath.'player.swf" type="application/x-shockwave-flash">';
}
/*
 * Load all plugins
 */
if(is_dir(dirname(__FILE__)."/lib/plugins")){
	$plugins=IDGL_File::getFileList(dirname(__FILE__)."/lib/plugins");
	foreach($plugins as $plugin){
		require_once dirname(__FILE__)."/lib/plugins/".$plugin."/".$plugin.".php";
	}
}


/******* menu class ******/

class Wp_Menu1{
	private $menu_items;
	public static $current_selection;
	public static $flat_map;
	public function Wp_Menu1($menu_items){
		$this->menu_items=array();

		foreach($menu_items as $item){
			$menu_item=$this->menu_items[$item->ID]=new Wp_MenuItem1($item,$item->menu_item_parent);
			if($item->menu_item_parent!=0){
				$this->menu_items[$item->menu_item_parent]->addChild($menu_item);
			}
		}
	}
	public function toString(){
		
		foreach($this->menu_items as $item){
			
			if($item->getParentID()==0){
				if($item->item->object_id==6) continue;
				$out.="<div class='elem_".$item->item->object_id."'>";
				$out.=$item->toString();
				$out.="</div>";
			}
		}
		
		return $out;
	}
	
	public function getSubMenu(){
		$current=Wp_Menu1::$current_selection;
		if(count($current->submenu)==0){
			$current=Wp_Menu1::$flat_map[$current->parent];
		}
		if(is_array($current->submenu)){
			foreach($current->submenu as $item){
					$out.=$item->toString();
			}
		}
		return $out;
	}
}
class Wp_MenuItem1{
	public $item;
	public $parent;
	public $submenu;
	public function Wp_MenuItem1($item,$parent=0,$submenu=null){
		$this->item=$item;
		$this->parent=$parent;
		if($this->submenu==null){
			$this->submenu=array();
		}
		global $wp_query;
		$post_obj = $wp_query->get_queried_object();
		$post_id = $post_obj->ID;
		//echo $post_id." -- ".$this->item->object_id."<br/>";
		if(Wp_Menu1::$current_selection==null && $post_id==$this->item->object_id){
			Wp_Menu1::$current_selection=$this;
		}
		Wp_Menu1::$flat_map[$this->item->ID]=$this;
	}
	public function addChild($item){
		$this->submenu[]=$item;
	}
	public function getParentID(){
		return $this->parent;
	}
	public function getType(){
		return $this->item->object;
	}
	public function toString(){
		if(Wp_Menu1::$current_selection==$this){
			if($this->parent==0){
				$out.="<h5>".$this->item->title."</h5>";
			}else{
				$out.="<a href='".$this->item->url."'>".$this->item->title."</a>";
			}
			
		}else{
			if($this->parent==0){
				$out.="<h5>".$this->item->title."</h5>";
			}else{
				$out.="<a href='".$this->item->url."'>".$this->item->title."</a>";
			}
		}
		
		if(count($this->submenu)>0){
			foreach($this->submenu as $item){
					$out.=$item->toString();
			}
		}
		return $out;
	}
}


$current_user = wp_get_current_user();
$user_type = $current_user -> _IDGL_elem_userSubtype;
$utype= $current_user -> _IDGL_elem_user_type;
//var_dump($user_type[0] == "demo-student" && $utype=="student" && (int)$user -> _IDGL_elem_expiration_date < time());
if($user_type[0] == "demo-student" && $utype=="student" && (int)$user -> _IDGL_elem_expiration_date > time())
{
	//echo "expired";
	if(Util::curPageURL()!=get_bloginfo("url")."/demo-expired/"){
		$redirect_to = get_bloginfo("url")."/demo-expired/";
		wp_safe_redirect( $redirect_to );
		exit();
	}
}