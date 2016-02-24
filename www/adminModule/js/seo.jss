$(document).ready(function() {
	// pokud se mi vygeneroval seo asistent v sablone
	if($('#seo-assistant').length > 0) {
		// defaultni jazyk
		if($("ul.langs").length) {
			var temp = $("ul.langs li.active a i").attr('class').match(/\bflag-icon-([a-z]{2})/)[1];
			if(temp == 'cz') temp = 'cs';
			if(temp == 'us') temp = 'en';
			SEO_switch_lang(temp);
		}
		
		SEO_initialize_values();
		
		// volani po zmene inputu
		$("input").keyup(function() {
			var name = $(this).attr('id').match(/-([^-]+)$/)[1];
			if($.inArray(name, allowedBaseElements) != -1) {
				SEO_input_changed($(this), name);
			}
		});
		
		// volani po zmene textarea
		$("textarea").keyup(function() {
			var name = $(this).attr('id').match(/-([^-]+)$/)[1];
			if($.inArray(name, allowedContentElements) != -1) {
				SEO_content_changed($(this), name);
			}
		});
		
		// tiny si zavola samo (definovano v loadTinyMCE jako event onLoadContent)
		
		// prepinani bloku podle zvoleneho jazyka
		$("ul.langs li a").click(function() {
			var temp = $(this).find('i').attr('class').match(/\bflag-icon-([a-z]{2})/)[1];
			if(temp == 'cz') temp = 'cs';
			if(temp == 'us') temp = 'en';
			
			SEO_switch_lang(temp);
		});
	}
	//
	
});

function SEO_initialize_values() {
	// nastaveni zakladnich hodnot po nacteni stranky pro defaultni jazyk
	$.each(allowedBaseElements, function( index, name ) {
		var input = $("#lang-" + lang + " input").filter(function() {
			var temp = this.id.match(new RegExp("-" + lang + "-" + name + "$"));
			if(temp != null) {
				SEO_input_changed($("#" + temp.input), name);
			}
	    });
	});
	
	// nastaveni hodnot pro obsah po nacteni stranky pro defaultni jazyk
	$.each(allowedContentElements, function( index, name ) {
		var input = $("#lang-" + lang + " textarea").filter(function() {
			var temp = this.id.match(new RegExp("-" + lang + "-" + name + "$"));
			if(temp != null) {
				if($("#" + temp.input).hasClass('tinymce')) {
					SEO_tiny_changed($("#" + temp.input));
				} else {
					SEO_content_changed($("#" + temp.input), name);
				}
			}
	    });
	});
}

function SEO_clean_text(s) {
	var temp = s.replace(/(^\s*)|(\s*$)/gi,"");
	temp = temp.replace(/[ ]{2,}/gi," ");
	temp = temp.replace(/\n /,"\n");
	
	return temp;
}

function SEO_headings_get(s) {
	var temp = [];
	
	$.each(SEO_clean_text(s).toLowerCase().match(/<h[0-9]{1}>/g) || [], function( index, word ) {
		temp.push(parseInt(word.replace(/[^0-9]/g, '')));
	});
	
	return temp;
}

function SEO_words_count(s) {
	var temp = SEO_clean_text(s).toLowerCase();
	var count = 0;

	// nepocitam stopslova
	$.each(temp.split(' '), function( index, word ) {
		if($.inArray(word, stopwords[lang]) == -1) {
			count++;
		}
	});
	
	return count;
}

function SEO_keywords_count(s,a) {
	var temp = SEO_clean_text(s).toLowerCase();
	var count = 0;
	var kw;
	
	$.each(temp.split(' '), function( index, word ) {
		//console.log('kw testing (', word, ')... ', $.inArray(word, a));
		if($.inArray(word, a) != -1) { 
			//console.log('kw found:', word);
			count++;
		}
	});
	
	return count;
}

function SEO_keywords_strength(s,words) {
	var temp = SEO_clean_text(s).toLowerCase();
	var spread = [];
	var total = 0;
	var i = 1;
	
	$.each(temp.split(' '), function( index, word ) {
		if($.inArray(word, kw[lang]) != -1) { 
			total += 1/i;
			//console.log('kw found:', word, i, total);
			spread[index] = 1;
		} else if($.inArray(word, kwe[lang]) != -1) { 
			total += 1/i * 0.5; // pro varianty kw je vaha slova nizsi
			//console.log('kwe found:', word, i, total);
			spread[index] = 1;
		} else if($.inArray(word, stopwords[lang]) == -1) {
			// pokud neni stopslovo, tak zvetsim delitele resp. snizim vahu dalsiho slova
			i = i + 1;
			spread[index] = 10;
		} else {
			spread[index] = 5;
		}
		
	});
	
	var primary = Math.round(total / words * 100);
	var head = 0;
	var tail = 0;
	var secondary = 0;

	$.each(spread, function( index, value ) {
		if(index < Math.floor(spread.length / 2)) {
			head += value;
		}
		if(index > Math.round(spread.length / 2)) {
			tail += value;
		}
	});
	
	if(head > tail) {
		secondary = Math.floor((100 - primary) * (tail / head));
	} else {
		secondary = Math.floor((100 - primary) * (head / tail));
	}
	
	console.log('strength head:', head);
	console.log('strength tail:', tail);
	console.log('strength spread:', spread);
	
	return [primary,secondary];
}

function SEO_headings_count(s) {
	return SEO_headings_get(s).length;
}

function SEO_headings_order(s) {
	var result = 'OK';
	var warnings = '';
	var headings = SEO_headings_get(s);
	
	//console.log('tiny headings', headings);
	
	// druhej je mensi nez prvni, pokud jsou aspon dva
	if(headings.length > 1 && headings[0] > headings[1]) {
		console.log('headings order: H', headings[0], ' before H', headings[1], 'at the begining!');
		warnings += 'Obsah začíná nadpisem nižší úrovně, než je následující. ';
		result = 'Nalezeny chyby';
	}
	
	$.each(headings, function( index, word ) {
		// pokud je nasledujici o dva vetsi nez aktualni (napr. H3 nasleduje po H1)
		if(headings[index + 1] && headings[index] + 2 <= headings[index + 1]) {
			console.log('headings order: H', headings[index + 1], ' after H', headings[index], '!');
			warnings += 'V obsahu jsou nenavazující úrovně nadpisů (např. H2 následovaný H4). ';
			result = 'Nalezeny chyby';
		}
	});
	
	if(warnings) {
		return '<span data-original-title="' + warnings+ '"  data-toggle="tooltip">' + result + '</span>';
	} else {
		return result;
	}
}

function SEO_input_changed(el, name) {
	var words = SEO_words_count(el.prop('value'));
	
	$('.base-data-'+name+' .hwc-' + lang).html(words);
	$('.base-data-'+name+' .hkwc-' + lang).html(SEO_keywords_count(el.prop('value'),kw[lang]));
	$('.base-data-'+name+' .hkwec-' + lang).html(SEO_keywords_count(el.prop('value'),kwe[lang]));
	var strength = SEO_keywords_strength(el.prop('value'),words);
	$('.base-data-'+name+' .hkws-' + lang).html(strength[0]);
	$('.base-data-'+name+' .hkwsa-' + lang).html(strength[1]);
	
	SEO_draw_chart(name);
}

function SEO_content_changed(el, name) {
	var words = SEO_words_count(el.prop('value'));
	
	$('.content-data-'+name+' .cwc-' + lang).html(words);
	$('.content-data-'+name+' .ckwc-' + lang).html(SEO_keywords_count(el.prop('value'),kw[lang]));
	$('.content-data-'+name+' .ckwec-' + lang).html(SEO_keywords_count(el.prop('value'),kwe[lang]));
	
	SEO_draw_chart(name);
} 

function SEO_tiny_changed(el) {
	if($(el).hasClass('tinymce')) {
		//console.log('textarea');
		var id = el.attr('id');
		var content = el.prop('value');
	} else {
		//console.log('tiny');
		var id = el.view.frameElement.id.replace('_ifr', '');
		var content = el.originalTarget.innerHTML;
	}

	// zbavime se tagu se zachovanim mezer mezi slovy
	text = $("<div/>").html(content.replace(/<\/[a-z0-9]+>/ig, ' ')).text();
	
	var name = id.match(/-([^-]+)$/)[1];

	$('.content-data-'+name+' .cwc-' + lang).html(SEO_words_count(text));
	$('.content-data-'+name+' .ckwc-' + lang).html(SEO_keywords_count(text, kw[lang]));
	$('.content-data-'+name+' .ckwec-' + lang).html(SEO_keywords_count(text, kwe[lang]));
	$('.content-data-'+name+' .chc-' + lang).html(SEO_headings_count(content));
	$('.content-data-'+name+' .cho-' + lang).html(SEO_headings_order(content));
	
	$('[data-toggle="tooltip"]').tooltip();
	
	SEO_draw_chart(name);
}

// ovladani zobrazeni podle jazyku 
function SEO_switch_lang(l) {
	lang = l;
	$("#seo-assistant div.well").hide();
	$("#seo-assistant div.well.lang-" + lang).show();
}

// BarChart
function SEO_draw_chart(name) {
	// graf klicovych slov inputu
	var hwc = parseInt($('.base-data-'+name+' .hwc-' + lang).text());
	var hkwc = parseInt($('.base-data-'+name+' .hkwc-' + lang).text());
	var hkwec = parseInt($('.base-data-'+name+' .hkwec-' + lang).text());
	var hkwp = Math.round(hkwc/hwc*100);
	var hkwep = Math.round(hkwec/hwc*100);
	$('.base-data-'+name+' .progress-hkwp-' + lang + ' .hkwp').html(hkwp+'%');
	$('.base-data-'+name+' .progress-hkwep-' + lang + ' .hkwep').html(hkwep+'%');
	if(ckwp + ckwep <= 10) { ckwep = ckwep * 10; ckwp = ckwp * 10; }
	$('.base-data-'+name+' .progress-hkwp-' + lang).width(hkwp+'%');
	$('.base-data-'+name+' .progress-hkwep-' + lang).width(hkwep+'%');
	
	// graf klicovych slov obsahu
	var cwc = parseInt($('.content-data-'+name+' .cwc-' + lang).text());
	var ckwc = parseInt($('.content-data-'+name+' .ckwc-' + lang).text());
	var ckwec = parseInt($('.content-data-'+name+' .ckwec-' + lang).text());
	var ckwp = Math.round(ckwc/cwc*100);
	var ckwep = Math.round(ckwec/cwc*100);
	$('.content-data-'+name+' .progress-ckwp-' + lang + ' .ckwp').html(ckwp+'%');
	$('.content-data-'+name+' .progress-ckwep-' + lang + ' .ckwep').html(ckwep+'%');
	if(ckwp + ckwep <= 10) {
		ckwep = ckwep * 10;
		ckwp = ckwp * 10;
		$('.content-data-'+name+' .progress-ckwp-' + lang).removeClass('progress-bar-warning progress-bar-danger').addClass('progress-bar-success');
		$('.content-data-'+name+' .progress-ckwep-' + lang).css('background-color', '#91E091');
	} else {
		$('.content-data-'+name+' .progress-ckwp-' + lang).removeClass('progress-bar-success progress-bar-danger').addClass('progress-bar-warning');
		$('.content-data-'+name+' .progress-ckwep-' + lang).css('background-color', '#FFBE6B');
	}
	$('.content-data-'+name+' .progress-ckwp-' + lang).width(ckwp+'%');
	$('.content-data-'+name+' .progress-ckwep-' + lang).width(ckwep+'%');
	
	// grafy sily
	var strength = parseInt($('.base-data-'+name+' .hkws-' + lang).text());
	var strength_additional = parseInt($('.base-data-'+name+' .hkwsa-' + lang).text());
	var strength_total = strength + strength_additional;
	
	console.log('strength', strength);
	console.log('strength_additional', strength_additional);
	console.log('strength_total', strength_total);
	
	if(strength_total >= 70) {
		$('.base-data-'+name+' .progress-hkws-' + lang).removeClass('progress-bar-warning progress-bar-danger').addClass('progress-bar-success');
		$('.base-data-'+name+' .progress-hkwsa-' + lang).css('background-color', '#91E091');
	}
	if(strength_total < 70 && strength_total >= 30) {
		$('.base-data-'+name+' .progress-hkws-' + lang).removeClass('progress-bar-success progress-bar-danger').addClass('progress-bar-warning');
		$('.base-data-'+name+' .progress-hkwsa-' + lang).css('background-color', '#FFBE6B');
	}
	if(strength_total < 30) {
		$('.base-data-'+name+' .progress-hkws-').removeClass('progress-bar-success progress-bar-warning').addClass('progress-bar-danger');
	}
	$('.base-data-'+name+' .progress-hkws-' + lang).width(strength+'%');
	$('.base-data-'+name+' .progress-hkwsa-' + lang).width(strength_additional+'%');
	
	/*
	var content_kw = parseInt($('#ckwc-' + lang).text());
	var content_total = parseInt($('#cwc-' + lang).text()) - content_kw;
	*/
}

// defaultni jazyk
var lang = 'cs'

// seznamy stop slov pro dane jazyky - to by chtelo jeste nejak domyslet pro jazyky definovany uzivatelemm aby to nehazelo chyby
var stopwords = [];
stopwords['cs'] = ['a','aby','aj','ale','ani','aniž','ano','asi','až','bez','bude','budem','budeš','by','byl','byla','byli','bylo','být','co','což','cz','či','článek','článku','články','další','dnes','do','ho','i','já','jak','jako','je','jeho','jej','její','jejich','jen','jenž','ještě','ji','jiné','již','jsem','jseš','jsme','jsou','jšte','k','kam','každý','kde','kdo','když','ke','která','které','kterou','který','kteři','ku','ma','máte','me','mě','mezi','mi','mít','mně','mnou','můj','může','my','na','ná','nad','nám','napište','náš','naši','ne','nebo','nechť','nejsou','není','než','ní','nic','nové','nový','o','od','ode','on','pak','po','pod','podle','pokud','pouze','práve','pro','proč','proto','protože','první','před','přede','přes','při','pta','re','s','se','si','sice','strana','své','svůj','svých','svým','svými','ta','tak','také','takže','tato','te','tě','tedy','těma','ten','tento','této ','tím','tímto','tipy','to','to','tohle','toho','tohoto','tom','tomto','tomuto','toto','tu','tuto','tvůj','ty','tyto','u','už','v','vám','váš','vaše','ve','více','však','všechen','vy','z','za','zda','zde','ze','zpět','zprávy','že'];
stopwords['sk'] = ['a','aby','aj','ak','ako','ale','alebo','and','ani','áno','asi','až','bez','bude','budem','budeš','budeme','budete','budú','by','bol','bola','boli','bolo','byť','cez','čo','či','ďalší','ďalšia','ďalšie','dnes','do','ho','ešte','for','i','ja','je','jeho','jej','ich','iba','iné','iný','som','si','sme','sú','k','kam','každý','každá','každé','každí','kde','keď','kto','ktorá','ktoré','ktorou','ktorý','ktorí','ku','lebo','len','ma','mať','má','máte','medzi','mi','mna','mne','mnou','musieť','môcť','môj','môže','my','na','nad','nám','náš','naši','nie','nech','než','nič','niektorý','nové','nový','nová','nové','noví','o','od','odo','of','on','ona','ono','oni','ony','po','pod','podľa','pokiaľ','potom','práve','pre','prečo','preto','pretože','prvý','prvá','prvé','prví','pred','predo','pri','pýta','s','sa','so','si','svoje','svoj','svojich','svojím','svojími','ta','tak','takže','táto','teda','te','tě','ten','tento','the','tieto','tým','týmto','tiež','to','toto','toho','tohoto','tom','tomto','tomuto','toto','tu','tú','túto','tvoj','ty','tvojími','už','v','vám','váš','vaše','vo','viac','však','všetok','vy','z','za','zo','že'];
stopwords['en'] = ['a','about','above','above','across','after','afterwards','again','against','all','almost','alone','along','already','also","although","always","am","among','amongst','amoungst','amount",  "an','and','another','any","anyhow","anyone","anything","anyway','anywhere','are','around','as",  "at','back","be","became','because","become","becomes','becoming','been','before','beforehand','behind','being','below','beside','besides','between','beyond','bill','both','bottom","but','by','call','can','cannot','cant','co','con','could','couldnt','cry','de','describe','detail','do','done','down','due','during','each','eg','eight','either','eleven","else','elsewhere','empty','enough','etc','even','ever','every','everyone','everything','everywhere','except','few','fifteen','fify','fill','find','fire','first','five','for','former','formerly','forty','found','four','from','front','full','further','get','give','go','had','has','hasnt','have','he','hence','her','here','hereafter','hereby','herein','hereupon','hers','herself','him','himself','his','how','however','hundred','ie','if','in','inc','indeed','interest','into','is','it','its','itself','keep','last','latter','latterly','least','less','ltd','made','many','may','me','meanwhile','might','mill','mine','more','moreover','most','mostly','move','much','must','my','myself','name','namely','neither','never','nevertheless','next','nine','no','nobody','none','noone','nor','not','nothing','now','nowhere','of','off','often','on','once','one','only','onto','or','other','others','otherwise','our','ours','ourselves','out','over','own","part','per','perhaps','please','put','rather','re','same','see','seem','seemed','seeming','seems','serious','several','she','should','show','side','since','sincere','six','sixty','so','some','somehow','someone','something','sometime','sometimes','somewhere','still','such','system','take','ten','than','that','the','their','them','themselves','then','thence','there','thereafter','thereby','therefore','therein','thereupon','these','they','thickv','thin','third','this','those','though','three','through','throughout','thru','thus','to','together','too','top','toward','towards','twelve','twenty','two','un','under','until','up','upon','us','very','via','was','we','well','were','what','whatever','when','whence','whenever','where','whereafter','whereas','whereby','wherein','whereupon','wherever','whether','which','while','whither','who','whoever','whole','whom','whose','why','will','with','within','without','would','yet','you','your','yours','yourself','yourselves','the'];
stopwords['de'] = ['aber','als','am','an','auch','auf','aus','bei','bin','bis','bist','da','dadurch','daher','darum','das','daß','dass','dein','deine','dem','den','der','des','dessen','deshalb','die','dies','dieser','dieses','doch','dort','du','durch','ein','eine','einem','einen','einer','eines','er','es','euer','eure','für','hatte','hatten','hattest','hattet','hier','hinter','ich','ihr','ihre','im','in','ist','ja','jede','jedem','jeden','jeder','jedes','jener','jenes','jetzt','kann','kannst','können','könnt','machen','mein','meine','mit','muß','mußt','musst','müssen','müßt','nach','nachdem','nein','nicht','nun','oder','seid','sein','seine','sich','sie','sind','soll','sollen','sollst','sollt','sonst','soweit','sowie','und','unser','unsere','unter','vom','von','vor','wann','warum','was','weiter','weitere','wenn','wer','werde','werden','werdet','weshalb','wie','wieder','wieso','wir','wird','wirst','wo','woher','wohin','zu','zum','zur','über'];
stopwords['pl'] = ['ach','aj','albo','bardzo','bez','bo','być','ci','cię','ciebie','co','czy','daleko','dla','dlaczego','dlatego','do','dobrze','dokąd','dość','dużo','dwa','dwaj','dwie','dwoje','dziś','dzisiaj','gdyby','gdzie','go','ich','ile','im','inny','ja','ją','jak','jakby','jaki','je','jeden','jedna','jedno','jego','jej','jemu','jeśli','jest','jestem','jeżeli','już','każdy','kiedy','kierunku','kto','ku','lub','ma','mają','mam','mi','mną','mnie','moi','mój','moja','moje','może','mu','my','na','nam','nami','nas','nasi','nasz','nasza','nasze','natychmiast','nią','nic','nich','nie','niego','niej','niemu','nigdy','nim','nimi','niż','obok','od','około','on','ona','one','oni','ono','owszem','po','pod','ponieważ','przed','przedtem','są','sam','sama','się','skąd','tak','taki','tam','ten','to','tobą','tobie','tu','tutaj','twoi','twój','twoja','twoje','ty','wam','wami','was','wasi','wasz','wasza','wasze','we','więc','wszystko','wtedy','wy','żaden','zawsze','że'];
stopwords['ru'] = ['а','е','и','ж','м','о','на','не','ни','об','но','он','мне','мои','мож','она','они','оно','мной','много','многочисленное','многочисленная','многочисленные','многочисленный','мною','мой','мог','могут','можно','может','можхо','мор','моя','моё','мочь','над','нее','оба','нам','нем','нами','ними','мимо','немного','одной','одного','менее','однажды','однако','меня','нему','меньше','ней','наверху','него','ниже','мало','надо','один','одиннадцать','одиннадцатый','назад','наиболее','недавно','миллионов','недалеко','между','низко','меля','нельзя','нибудь','непрерывно','наконец','никогда','никуда','нас','наш','нет','нею','неё','них','мира','наша','наше','наши','ничего','начала','нередко','несколько','обычно','опять','около','мы','ну','нх','от','отовсюду','особенно','нужно','очень','отсюда','в','во','вон','вниз','внизу','вокруг','вот','восемнадцать','восемнадцатый','восемь','восьмой','вверх','вам','вами','важное','важная','важные','важный','вдали','везде','ведь','вас','ваш','ваша','ваше','ваши','впрочем','весь','вдруг','вы','все','второй','всем','всеми','времени','время','всему','всего','всегда','всех','всею','всю','вся','всё','всюду','г','год','говорил','говорит','года','году','где','да','ее','за','из','ли','же','им','до','по','ими','под','иногда','довольно','именно','долго','позже','более','должно','пожалуйста','значит','иметь','больше','пока','ему','имя','пор','пора','потом','потому','после','почему','почти','посреди','ей','два','две','двенадцать','двенадцатый','двадцать','двадцатый','двух','его','дел','или','без','день','занят','занята','занято','заняты','действительно','давно','девятнадцать','девятнадцатый','девять','девятый','даже','алло','жизнь','далеко','близко','здесь','дальше','для','лет','зато','даром','первый','перед','затем','зачем','лишь','десять','десятый','ею','её','их','бы','еще','при','был','про','процентов','против','просто','бывает','бывь','если','люди','была','были','было','будем','будет','будете','будешь','прекрасно','буду','будь','будто','будут','ещё','пятнадцать','пятнадцатый','друго','другое','другой','другие','другая','других','есть','пять','быть','лучше','пятый','к','ком','конечно','кому','кого','когда','которой','которого','которая','которые','который','которых','кем','каждое','каждая','каждые','каждый','кажется','как','какой','какая','кто','кроме','куда','кругом','с','т','у','я','та','те','уж','со','то','том','снова','тому','совсем','того','тогда','тоже','собой','тобой','собою','тобою','сначала','только','уметь','тот','тою','хорошо','хотеть','хочешь','хоть','хотя','свое','свои','твой','своей','своего','своих','свою','твоя','твоё','раз','уже','сам','там','тем','чем','сама','сами','теми','само','рано','самом','самому','самой','самого','семнадцать','семнадцатый','самим','самими','самих','саму','семь','чему','раньше','сейчас','чего','сегодня','себе','тебе','сеаой','человек','разве','теперь','себя','тебя','седьмой','спасибо','слишком','так','такое','такой','такие','также','такая','сих','тех','чаще','четвертый','через','часто','шестой','шестнадцать','шестнадцатый','шесть','четыре','четырнадцать','четырнадцатый','сколько','сказал','сказала','сказать','ту','ты','три','эта','эти','что','это','чтоб','этом','этому','этой','этого','чтобы','этот','стал','туда','этим','этими','рядом','тринадцать','тринадцатый','этих','третий','тут','эту','суть','чуть','тысяч'];
stopwords['it'] = ['a','adesso','ai','al','alla','allo','allora','altre','altri','altro','anche','ancora','avere','aveva','avevano','ben','buono','che','chi','cinque','comprare','con','consecutivi','consecutivo','cosa','cui','da','del','della','dello','dentro','deve','devo','di','doppio','due','e','ecco','fare','fine','fino','fra','gente','giu','ha','hai','hanno','ho','il','indietro','invece','io','la','lavoro','le','lei','lo','loro','lui','lungo','ma','me','meglio','molta','molti','molto','nei','nella','no','noi','nome','nostro','nove','nuovi','nuovo','o','oltre','ora','otto','peggio','pero','persone','piu','poco','primo','promesso','qua','quarto','quasi','quattro','quello','questo','qui','quindi','quinto','rispetto','sara','secondo','sei','sembra','sembrava','senza','sette','sia','siamo','siete','solo','sono','sopra','soprattutto','sotto','stati','stato','stesso','su','subito','sul','sulla','tanto','te','tempo','terzo','tra','tre','triplo','ultimo','un','una','uno','va','vai','voi','volte','vostro'];
stopwords['fr'] = ['alors','au','aucuns','aussi','autre','avant','avec','avoir','bon','car','ce','cela','ces','ceux','chaque','ci','comme','comment','dans','des','du','dedans','dehors','depuis','devrait','doit','donc','dos','début','elle','elles','en','encore','essai','est','et','eu','fait','faites','fois','font','hors','ici','il','ils','je','juste','la','le','les','leur','là','ma','maintenant','mais','mes','mine','moins','mon','mot','même','ni','nommés','notre','nous','ou','où','par','parce','pas','peut','peu','plupart','pour','pourquoi','quand','que','quel','quelle','quelles','quels','qui','sa','sans','ses','seulement','si','sien','son','sont','sous','soyez','sujet','sur','ta','tandis','tellement','tels','tes','ton','tous','tout','trop','très','tu','voient','vont','votre','vous','vu','ça','étaient','état','étions','été','être'];
stopwords['hu'] = ['a','az','egy','be','ki','le','fel','meg','el','át','rá','ide','oda','szét','össze','vissza','de','hát','és','vagy','hogy','van','lesz','volt','csak','nem','igen','mint','én','te','õ','mi','ti','õk','ön'];
stopwords['nl'] = ['aan','af','al','als','bij','dan','dat','die','dit','een','en','er','had','heb','hem','het','hij','hoe','hun','ik','in','is','je','kan','me','men','met','mij','nog','nu','of','ons','ook','te','tot','uit','van','was','wat','we','wel','wij','zal','ze','zei','zij','zo','zou'];