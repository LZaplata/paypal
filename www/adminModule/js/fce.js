$(document).ready(function() {		
	$('a[data-confirm], button[data-confirm], input[data-confirm]').live('click', function (e) {
        if (!confirm($(this).attr('data-confirm'))) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
        }
	});
	
	$('.actions .datagrid-ajax').click(function(e){
        if(confirm("Opravdu smazat?")){
                return true;
        }else{
                return false;
        }
	});
	
	/**
	 * inicialize tooltip
	 */
	$("[data-toggle='tooltip']").tooltip({
		html: true
	});
	
	/**
	 * inicialize tooltip in grid
	 */
	$(".grid *[title]").tooltip({});
	$(document).ajaxStop(function () {
		$(".grid *[title]").tooltip({});
	});
	
	//načtení bloků, pokud edituji stránku
	var load = $("#frm-addForm").attr("loadBlocks");
	if (load == "true") {
		loadBlocks();
	}
	
	//načtení bloků, pokud edituji produkt
	var load = $("#frm-addForm").attr("changeProductType");
	if (load == "true") {
		changeProductType();
	} 
	
	//načtení bloků, pokud edituji variace produktu
	var load = $("#frm-variations").attr("changeVariations");
	if (load == "true") {
		changeVariations();
	}
	
	//konfigurace dateinput pluginu
	$(".date").live('focus', function () {
		var id = $(this).attr("id");
		var id = id.replace("altDate", "date");
		
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#date",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	$(".grid-datepicker").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "HH:mm"
		});
	});
	
	$("#frm-addForm-altExpirationDateFrom").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#frm-addForm-expirationDateFrom",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	$("#frm-addForm-altExpirationDateTo").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#frm-addForm-expirationDateTo",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	$("#frm-addForm-infos-altExpirationDateFrom").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#frm-addForm-infos-expirationDateFrom",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	$("#frm-addForm-infos-altExpirationDateTo").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#frm-addForm-infos-expirationDateTo",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	$(".datepicker").live('focus', function () {
		var alt = $(this).attr('id');
		var alt = alt.replace("Alt", "");
		
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altField: "#"+alt,
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});
	
	//uploader
	if (window.location.href.indexOf('?') > 0) {
		var char = '&';
	}
	else {
		var char = '?';
	}
	
	$('#imagesUploader').pluploadQueue({
		runtimes: 'html5, html4, flash',
//		flash_swf_url : 'plupload.flash.swf',
		filters: [
		          {title : "Image files", extensions : "jpg,gif,png"}
		],
		url: window.location.href+char+'do=gallery-upload',
		
		preinit : {
			UploadComplete: function(up, file) {
				window.location.reload();
			}
		}
	});
	
	$("#imagesUploader_browse").attr("class", "btn btn-default btn-sm");
	$(".plupload_start").addClass("btn btn-primary btn-sm");
	
	$('#filesUploader').pluploadQueue({
		runtimes: 'html5, html4, flash',
//		flash_swf_url : 'plupload.flash.swf',
		max_file_size : '60mb',
		filters: [
		          {title : "Image files", extensions : "jpg,gif,png"},
		          {title : "Other files", extensions : "zip,rar,doc,docx,xls,xlsx,pdf,ods,ppt,pptx"}
		],
		url: window.location.href+char+'do=files-upload',
		
		preinit : {
			UploadComplete: function(up, file) {
				window.location.reload();
			}
		}
	});
	
	$("#filesUploader_browse").attr("class", "btn btn-default btn-sm");
	$(".plupload_start").addClass("btn btn-primary btn-sm");
	
	//fancybox
	$("a:has(img)").live("mouseover", function () {
		$(this).fancybox({
			autoScale: true,
			titleShow: false
		});
		return false;
	});
	
	$("a.fancybox").fancybox({
		autoScale: true,
		titleShow: false,
		type: 'iframe',
		scrollOutside: false,
		closeBtn: false,
		width: '100%',
		maxWidth: '1200px',
		height: '100%',
		helpers : {
			overlay : {
				closeClick : false,
			}
		}
	});
	
	//cropper	
	var image = $("#cropper");
	
	$.fn.cropper.setDefaults({
		done: function (data) {
			$("#frm-gallery-cropForm input[name='left']").val(data.x1);
			$("#frm-gallery-cropForm input[name='top']").val(data.y1);
			$("#frm-gallery-cropForm input[name='width']").val(data.width);
			$("#frm-gallery-cropForm input[name='height']").val(data.height);
			$(".dimensions .width").html(data.width);
			$(".dimensions .height").html(data.height);
		}
	});
	
	$('.cropImage').click(function () {
		var width = $(this).attr('data-width');
		var height = $(this).attr('data-height');
		
		$("#frm-gallery-cropForm input[name='originalWidth']").val(width);
		$("#frm-gallery-cropForm input[name='originalHeight']").val(height);
		
		$(".dimensions").show();
		
		image.cropper("enable");
		image.cropper("setAspectRatio", width/height);
	});
	
	//multisortable
	var pid = null;
	var keycode;
	
	$('.position tbody tr .move').live('click', function (ui, e) {
		$(document).keydown(function (e) {
			keycode = e.keyCode;
		});
		$(document).keyup(function (e) {
			keycode = null;
		});
		
		$('.position tbody tr').removeClass('multiselectable-previous');

		if (keycode != 17) {
			$('.position tbody tr .move').removeClass('fa-minus').addClass('fa-plus');
			$('.position tbody tr').removeClass('selected active');
		}
		
		$(this).addClass('fa-minus');
		$(this).parent().parent().addClass('selected multiselectable-previous active');
		
		pid = $(this).parent().parent().attr('pid');
		
		$('.position tbody tr[pid="'+pid+'"]').each(function() {
			$(this).addClass('sortable');
			
			if ($(this).next().attr('pid') > pid) {
				if ($('.position tbody tr[pid="'+pid+'"]').length == 1) {
					pid = $('.position tbody tr[id="'+pid+'"]').attr('pid');
				}
				
				if ($('.position tbody tr[pid="'+($(this).attr('pid'))+'"]').length > 1) {
					$(this).nextUntil('tr[pid="'+pid+'"]').fadeOut(300);
				}
			}
		});
	});
	
	//browser functions	
	$("#browser-images li span").click(function () {
		$(this).siblings("ul").toggle();
		$(this).toggleClass("open");
	});
	
	$("#browser-images li ul:not(.hidden)").siblings("span").addClass("open").siblings("ul").show();
	
	
	$("#content #left ul").has("li.current").show().parent().children().show();
	$("#content #left ul li ul:visible").siblings("span").addClass("clicked");
//	
	$("#content #left ul li span").live("click", function () {
		$(this).toggleClass('clicked').siblings("ul").toggle();
	});
	
	$("input[name*='name']").live("keyup", function () {
		var input = $(this);
		
		createUrl(input);
	});
	
	$("#frmaddForm-settings-type, #frmaddForm-settings-pid").live("change", function () {
		changeProductType();
	});
	
	loadMultiSortable();
	loadChosen();
	
// vyhledavani kategorii	
	$(document).on("keypress", '#category-filter input[name="q"]', function(e) {
	     if (e.which == 13) {
	    	 $('#category-filter input[type="button"]').trigger('click');
	     }
	});

	$('#category-filter input[type="button"]').click(function() {
		var q = $('#category-filter input[name="q"]').attr('value');
		
		if(q.length >= 3) {
			$('#category-filter ul').addClass('collapse');
			$('#category-filter ul span').removeClass('alert-success');
			//console.log($('#category-filter > ul > li > a').length);
			$('#category-filter li > span').each(function() {
				var text = $(this).text().trim();
				if(text.search(new RegExp(q.toLowerCase(), 'i')) > -1) {
					//console.log('found ' + q.toLowerCase() + ' in ' + text);
					$(this).parents().removeClass('collapse');
					$(this).addClass('alert-success');
				} else {
					//console.log(text + ' score: ' + text.search(new RegExp(q.toLowerCase(), 'i')));
				}
			});
		} else {
			alert("Zadejte alespoň 3 znaky");
		}
	});
});

function loadMultiSortable () {
	$('.position tbody').multisortable({
		stop: function (event, ui) {
			var order = $('.position tbody').sortable('toArray');
			$.get('?do=changeOrder', {'positions[]' : order});
		},
		items: '.sortable'
	});
	$('.position tbody.gallery').multisortable({
		stop: function (event, ui) {
			var order = $('.position tbody.gallery').sortable('toArray');
			$.get(window.location.href+'&do=gallery-changeOrder', {'positions[]' : order});
		},
		items: '.sortable'
	});
	$('.position tbody.files').multisortable({
		stop: function (event, ui) {
			var order = $('.position tbody.files').sortable('toArray');
			$.get(window.location.href+'&do=files-changeOrder', {'positions[]' : order});
		},
		items: '.sortable'
	});
	$('.position tbody tr').disableSelection();
	
	$("#frm-addMail-content-modules_id").live("change", function () {
		changeMailContent();
	});
}

function loadBlocks() {	
	if (window.location.href.indexOf('?') > 0) {
		var char = '&';
	}
	else {
		var char = '?';
	}
	
	$.get(this.location.href+char+"do=loadBlocks", $("#frm-addForm fieldset:not(.submit)").serialize());
}

function changeProductType() {
	$.get(location.href+"&do=changeProductType", $("#frm-addForm").serialize());
}

function changeMailContent() {
	$.get("?do=changeMailContent", $("#frm-addMail fieldset:not(.submit)").serialize());
}

function changeModule() {
	$.get("?do=tags-changeModule", $("#frm-tags-addTag").serialize());
}

function addContent () {
	$.get("?do=addContent", $("#frm-addMail fieldset:not(.submit) *:not(.btn)").serialize());
}

function addThumb () {
	$.get("?do=addThumb", $("#frm-addSectionForm").serialize());
}

function addField () {
	$.get("?do=addField", $("#frm-addSectionForm").serialize());
}

//funkce pro vytvoření url
function createUrl() {
	var s = $("input[name*='name']").val();
	var nodiac = { 'á': 'a', 'č': 'c', 'ď': 'd', 'é': 'e', 'ě': 'e', 'í': 'i', 'ň': 'n', 'ó': 'o', 'ř': 'r', 'š': 's', 'ť': 't', 'ú': 'u', 'ů': 'u', 'ý': 'y', 'ž': 'z' };

	s = s.toLowerCase();
	var s2 = '';
	for (var i=0; i < s.length; i++) {
		s2 += (typeof nodiac[s.charAt(i)] != 'undefined' ? nodiac[s.charAt(i)] : s.charAt(i));
	}

	$("input[name*='url']").val(s2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, ''));
}

function updateCoords (c) {
	console.log(c);
	
	$("#frm-gallery-cropForm-left").val(c.x);
	$("#frm-gallery-cropForm-top").val(c.y);
	$("#frm-gallery-cropForm-width").val(c.w);
	$("#frm-gallery-cropForm-height").val(c.h);
}

//function changeMultiVisibility (items, vis, component, char) {
//	$.get(window.location.href+char+"do="+component+"multiVisibility", {'items[]':items, 'vis':vis});
//}
//
//function changeMultiHighlight (items, vis, component, char) {
//	$.get(window.location.href+char+"do="+component+"multiHighlight", {'items[]':items, 'vis':vis});
//}
//
//function multiDelete (items, component, char) {
//	$.get(window.location.href+"?do="+component+"multiDelete", {'items[]':items});
//}

function updatePrice (param, parent) {
	var price = $('#frmaddForm-price').val();
	
	if (param == 0) {
		if (parseInt(parent.val()) <= parseInt(price)) {
			percentage = 100 - ((parent.val() / price) * 100);
		}
		else {
			percentage = 0;
		}
		
		$('#frmaddForm-discountPercentage').val(percentage);
	}
	else {
		if (parseInt(parent.val()) > 100) {
			discount = 0;
		}
		else {
			discount = price - (price * (parent.val() / 100));
		}
		
		$('#frmaddForm-discountAmount').val(discount);
	}
}

function loadTinyMCE () {	
	tinymce.init({
	    selector: "textarea.tinymce",
	    language: "cs",
	    theme: "modern",
	    skin: "light",
	    width: "100%",
	    height: "300",
	    autoresize_min_height: "300",
	    autoresize_max_height: "1000",
	    plugins: "autoresize, table, link, anchor, image, code, paste, autolink, media",
	    image_advtab: true,
	    style_formats_merge: true,
	    style_formats: [
						{title: 'Odstavec', block: 'p'},
						{title: "Nadpisy", items: [
							{title: 'Nadpis 1', block: 'h1'},
							{title: 'Nadpis 2', block: 'h2'},
							{title: 'Nadpis 3', block: 'h3'},
							{title: 'Nadpis 4', block: 'h4'},
							{title: 'Nadpis 5', block: 'h5'},
						]},
						{title: 'Responzivní obrázek', selector: 'img', classes: "img-responsive"},
						{title: 'Vertikální zarovnání', selector: 'td', styles: {'vertical-align': 'top'}},
						{title: "Tabulka", items: [
			                             {title: "Základní", selector: "table", classes: "table"},
			                             {title: "Bez ohraničení", selector: "td", styles: {'border-top': 'none'}},
			                             {title: "S ohraničením", selector: "table", classes: "table table-bordered"}
			            ]},
	    ],
	    menubar: "format table",
	    toolbar: "undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link anchor | image media | removeformat | code",
	    relative_urls: false,
	    remove_script_host: false,	    
//	    convert_urls: false,
	    entity_encoding: 'named',
	    paste_as_text: true,
	    
	    // volani SEO funkce po uprave obsahu
	    setup: function(editor) {
	    	editor.on('keyup change', function(e) {
	    		if (typeof SEO_tiny_changed == 'function') { SEO_tiny_changed(e); }
	    	});
	    },

	    file_browser_callback : function (field_name, url, type, win) {
	    	var url = window.location.toString();
			var pages = url.split('/');
			var browser = '', stop;
			
			for (var i=0; i<pages.length; i++) {
				if (pages[i] != 'admin') {
					if (stop != 1) {
						browser = browser+pages[i]+'/';
					}
				}
				else {
					stop = 1;
				}  
			}
			
			tinymce.activeEditor.windowManager.open({
			    file : browser+'admin/browser/'+type,
			    title : 'File manager',
			    width : 960,
			    height : 600,
			    resizable : "no",
			    close_previous : "no"
			});
			return false;
	    }
	});
}

function addUrl (url, alt) {
	var altInput = $(top.document).find("div[role=dialog] input:eq(1)");

	$(top.document).find("div[role=dialog] input:first").val(url);
	if (altInput.val() == '') {
		altInput.val(alt);
	}
	
	top.tinymce.activeEditor.windowManager.close();
}

function changeOrderState (id) {
	var state = $("#order"+id+" option:selected").val();
	
	$.get('?do=changeOrderState', {'id':id, 'state': state});
}

function changeProductState (id) {
	var state = $("#product"+id+" option:selected").val();
	
	$.get('?do=changeProductState', {'id':id, 'state': state});
}

function loadChosen () {
	$(".chosen").chosen();
	$(".chosen.reload").change(function () {
		changeVariations();
	});
	$(".chosen.product").change(function(){
		changeProduct();		
	});
}

function changeVariations () {
	$.get("?sid=0&do=changeVariations&", $("#frm-variations fieldset:not(.submit)").serialize());
//	location.href="?sid=0&do=changeVariations&"+$("#frm-variations").serialize();
}