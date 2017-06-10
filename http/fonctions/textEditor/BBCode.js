function affColor(id){

	var myColor = new jscolor.color(document.getElementById('colorInput-'+id),id);
	myColor.showPicker();
	
}


function addColor(id){
	

	var saisie = document.getElementById(id);
	var valeur = document.getElementById('colorInput-'+id).value;

	saisie.focus();

	var scroll = saisie.scrollTop;
	var selectDeb = saisie.selectionStart;
	var selectEnd = saisie.selectionEnd;
	var avant = saisie.value.substring(0, selectDeb);
	var contenu = saisie.value.substring(selectDeb, selectEnd);
	var apres = saisie.value.substring(selectEnd);
	saisie.value = avant + '[color='+valeur+']' + contenu + '[/color]' + apres;
	
	saisie.setSelectionRange(avant.length + valeur.length+8, avant.length + valeur.length+8 + contenu.length);
	saisie.scrollTop = scroll;
	majPreview(id);

}


function addBaliseSimple(id, balise){
	var saisie = document.getElementById(id);
	
	saisie.focus();
	
	var scroll = saisie.scrollTop;
	var selectDeb = saisie.selectionStart;
	var selectEnd = saisie.selectionEnd;
	var avant = saisie.value.substring(0, selectDeb);
	var contenu = saisie.value.substring(selectDeb, selectEnd);
	var apres = saisie.value.substring(selectEnd);
	
	saisie.value = avant + '['+balise+']' + contenu + '[/'+balise+']' + apres;
	
	saisie.setSelectionRange(avant.length + balise.length+2, avant.length + balise.length+2 + contenu.length);
	saisie.scrollTop = scroll;
	majPreview(id);
	
}


function addBaliseAlign(id, balise) {
	var saisie = document.getElementById(id);

	saisie.focus();

	var scroll = saisie.scrollTop;
	var selectDeb = saisie.selectionStart;
	var selectEnd = saisie.selectionEnd;
	var avant = saisie.value.substring(0, selectDeb);
	var contenu = saisie.value.substring(selectDeb, selectEnd);
	var apres = saisie.value.substring(selectEnd);
	saisie.value = avant + '[align='+balise+']' + contenu + '[/align]' + apres;
	
	saisie.setSelectionRange(avant.length + balise.length+8, avant.length + balise.length+8 + contenu.length);
	saisie.scrollTop = scroll;
	majPreview(id);

}

function addBaliseUrl(id) {
	var saisie = document.getElementById(id);

	var regUrl = new RegExp('^((http|https):\/\/)?(www[.])?([a-zA-Z0-9]|-)+([.][a-zA-Z0-9(-|\/|=|?)?]+)+$', 'i');
	var regMail = new RegExp('^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$','i');
	
	saisie.focus();

	var scroll = saisie.scrollTop;
	var selectDeb = saisie.selectionStart;
	var selectEnd = saisie.selectionEnd;
	var avant = saisie.value.substring(0, selectDeb);
	var contenu = saisie.value.substring(selectDeb, selectEnd);
	var apres = saisie.value.substring(selectEnd);

	if(contenu.match(regUrl) || contenu == '') {
		saisie.value = avant + '[url{' + contenu + '}][/url]' + apres;
		saisie.setSelectionRange(avant.length + 5 + contenu.length + 2, avant.length + 5 + contenu.length + 2);
		
	}else if(contenu.match(regMail)) {
		saisie.value = avant + '[url{mailto:' + contenu + '}][/url]' + apres;
		saisie.setSelectionRange(avant.length + 12 + contenu.length + 2, avant.length + 12 + contenu.length + 2);
	
	}else {
		saisie.value = avant + '[url{}]' + contenu + '[/url]' + apres;
		saisie.setSelectionRange(avant.length + 5, avant.length + 5);
	
	}
	
	saisie.scrollTop = scroll;
	majPreview(id);
}

function majPreview(id){
	
	var regBB = Array();
	var repBB = Array();
	
	var preview = document.getElementById(id).value;
	
	preview = strip_tags(preview, null);
	
	regBB[0] = new RegExp("\\n","g");
	repBB[0] = "<br />";
	
	regBB[1] = new RegExp("\\[b\\](.*?)\\[/b\\]","gi");
	repBB[1] = "<b>$1</b>";
	
	regBB[2] = new RegExp("\\[i\\](.*?)\\[/i\\]","gi");
	repBB[2] = "<em>$1</em>";
	
	regBB[3] = new RegExp("\\[u\\](.*?)\\[/u\\]","gi");
	repBB[3] = "<span style=\"text-decoration: underline\">$1</span>";
	
	regBB[4] = new RegExp("\\[s\\](.*?)\\[/s\\]","gi");
	repBB[4] = "<span style=\"text-decoration: line-through\">$1</span>";

	regBB[5] = new RegExp("\\[align=(left|center|right)\\](.*?)\\[/align\\]","gi");
	repBB[5] = "<div style=\"text-align:$1\">$2</div>";
	regBB[6] = new RegExp("</div><br />","gi");
	repBB[6] = "</div>";
	
	regBB[7] = new RegExp("\\[li\\](.*?)\\[/li\\]","gi");
	repBB[7] = "<ul><li>$1</li></ul>";
	regBB[8] = new RegExp("</ul>((<br />| )*?)<ul>","gi");
	repBB[8] = "";
	
	regBB[9] = new RegExp("\\[url\\]([^\"'();<>]*?)\\[/url\\]","gi");
	repBB[9] = "<a href=\"$1\" target=\"_blank\">$1</a>";
	regBB[10] = new RegExp("\\[url{([^\"'();<>]+?)}\\](.*?)\\[/url\\]","gi");
	repBB[10] = "<a href=\"$1\" target=\"_blank\">$2</a>";

	regBB[11] = new RegExp("\\[color=(#(?:[0-9a-f]{3}|[0-9a-f]{6}))\\](.*?)\\[/color\\]","gi");
	repBB[11] = "<span style=\"color: $1\">$2</span>";

	
	
	for(bb in regBB){
		
		preview = preview.replace(regBB[bb],repBB[bb]);
	}
	
	
	document.getElementById('preview-'+id).innerHTML = preview;
}

function strip_tags(input, allowed) {

  allowed = (((allowed || '') + '')
    .toLowerCase()
    .match(/<[a-z][a-z0-9]*>/g) || [])
    .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
    commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
  return input.replace(commentsAndPhpTags, '')
    .replace(tags, function($0, $1) {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}