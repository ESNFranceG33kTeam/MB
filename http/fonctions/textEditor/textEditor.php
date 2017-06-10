<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');
function initBBCode(){
	
	echo '<script type="text/javascript" src="/fonctions/textEditor/jscolor.js"></script>
		<script type="text/javascript" src="/fonctions/textEditor/BBCode.js"></script>
		';
	
}


function addTextAreaBBCode($id, $name, $style=null, $value=null){
	
	$path = '/fonctions/textEditor/';
	
	echo '
		<div style="height:32px;">
			<a onclick="addBaliseSimple(\''.$id.'\',\'b\')" ><img class="textEditor" src="'.$path.'bold.jpg" title="Gras"></a>
			<a onclick="addBaliseSimple(\''.$id.'\',\'i\')" ><img class="textEditor" src="'.$path.'italic.jpg" title="Italique"></a>
			<a onclick="addBaliseSimple(\''.$id.'\',\'u\')" ><img class="textEditor" src="'.$path.'underline.jpg" title="Souligné"></a>
			<a onclick="addBaliseSimple(\''.$id.'\',\'s\')" ><img class="textEditor" src="'.$path.'barre.jpg" title="Barré"></a>
			
			<a onclick="addBaliseAlign(\''.$id.'\',\'left\')" ><img class="textEditor" src="'.$path.'left.jpg" title="Aligner à gauche"></a>
			<a onclick="addBaliseAlign(\''.$id.'\',\'center\')" ><img class="textEditor" src="'.$path.'center.jpg" title="Centrer"></a>
			<a onclick="addBaliseAlign(\''.$id.'\',\'right\')" ><img class="textEditor" src="'.$path.'right.jpg" title="Aligner à droite"></a>
			
			<a onclick="addBaliseSimple(\''.$id.'\', \'li\')" ><img class="textEditor" src="'.$path.'list.jpg" title="Liste"></a>
			
			<a onclick="addBaliseUrl(\''.$id.'\')" ><img class="textEditor" src="'.$path.'link.jpg" title="Lien / Mail"></a>
			
			<a onclick="affColor(\''.$id.'\')" style="padding-right:0">
				<img class="textEditor" src="'.$path.'colorBis.jpg" title="Couleur"><input id="colorInput-'.$id.'" class="colorInput" value="#000000">
			</a>
		</div>
		
		<textarea id="'.$id.'" name="'.$name.'" autocomplete="off" style="margin-bottom:3px;'.$style.'" onkeyup="majPreview(this.id)">'.$value.'</textarea>
		<div class="blocText" id="preview-'.$id.'" style="margin-bottom:15px"></div>
		';
	 
	 
	 
	//Init preview
	if(!empty($value)){
		echo '<script type="text/javascript">majPreview(\''.$id.'\')</script>';
	}
	
}

function bbCodeToHTML($text){

	$text = preg_replace("#\\n#iUs","<br />",$text);

	$text = preg_replace("#\[b\](.*)\[/b\]#iUs","<b>$1</b>",$text);
	$text = preg_replace("#\[i\](.*)\[/i\]#iUs","<em>$1</em>",$text);
	$text = preg_replace("#\[u\](.*)\[/u\]#iUs","<span style=\"text-decoration: underline\">$1</span>",$text);
	$text = preg_replace("#\[s\](.*)\[/s\]#iUs","<span style=\"text-decoration: line-through\">$1</span>",$text);

	$text = preg_replace("#\[align=(left|center|right)\](.*)\[/align\]#iUs","<div style=\"text-align:$1\">$2</div>",$text);
	$text = preg_replace("#</div><br />#iUs","</div>",$text);
	
	$text = preg_replace("#\[li\](.*)\[/li\]#iUs","<ul><li>$1</li></ul>",$text);
	$text = preg_replace("#</ul>((<br />| )*)<ul>#iUs","",$text);

	$text = preg_replace("#\[url\]([^\"'();<>]*)\[/url\]#iUs","<a href=\"$1\" target=\"_blank\">$1</a>",$text);
	$text = preg_replace("#\[url{([^\"'();<>]+)}\](.*)\[/url\]#iUs","<a href=\"$1\" target=\"_blank\">$2</a>",$text);

	$text = preg_replace("#\[color=(\#(?:[0-9a-f]{3}|[0-9a-f]{6}))\](.*)\[/color\]#iUs","<span style=\"color: $1\">$2</span>",$text);

	return $text;	
}
?>