$(document).ready(function() {	
	$('[data-confirm]').click(function(e){
        if(confirm($(this).data().confirm + "?")){
                return true;
        }else{
                return false;
        }
	});
	
	$("#gallery ul li a:has(img), .editor-gallery ul li a:has(img), .product-detail .img a:has(img), .fancybox").fancybox({
		autoScale: true,
		titleShow: false,
		helpers: {
			overlay: {
				locked: false,
				showEarly: false
			}
		},
		openSpeed: 10,
		closeSpeed: 10,
		nextEffect: "none",
		prevEffect: "none"
	});
	
//	$("#slider").hucrSlider();
	
	$("#cart-popup .btn-default").live("click", function () {
		$("#cart-popup").remove();
	});
	
	$("input[name='transport_id'], input[name='payment_id'], select[name='zasilkovna']").live("change", function () {
		$.get("?do=changeMethods", $("#frm-transport input[type='radio'], #frm-transport select[name='zasilkovna']").serialize());
	});
	
	$("#frm-searchForm-dateFrom, #frm-searchForm-dateTo").datepicker();
	
	//tabs v detailu produktu
	$('#product-tabs a').live('click', function (e) {
		e.preventDefault();

		var tab = $(this);
		
		if ($(this).parent().is(".posts-tab")) {
			$.get("?do=getPosts");
			
			$(document).ajaxStop(function () {
				tab.tab("show");
			});
		}
		else {
			tab.tab("show");
		}
	});
	
	$(document).ajaxStop(function () {
		if ($("#add-post").is(":visible")) {
			$('html, body').animate({
				scrollTop: $("#add-post").offset().top
			}, 300);
		}
	});
	
	/**
	 * filters
	 */
	var min = parseInt($("#price-filter").attr("data-min"));
	var max = parseInt($("#price-filter").attr("data-max"));
	var minSelect = parseInt($("#price-filter").attr("data-min-select"));
	var maxSelect = parseInt($("#price-filter").attr("data-max-select"));
	
	$("#price-filter").slider({
		range: true,
		min: min,
		max: max,
		values: [minSelect, maxSelect],
		change: function (e, ui) {			
			$.get(window.location.href + getQueryCharacter() + "do=priceFilter-filter", {range : ui.values});
		},
		slide: function (e, ui) {
			$(".min" ).html(ui.values[0]);
			$(".max" ).html(ui.values[1]);
		}
	});
	
	$("#properties-filter").on("change", function () {		
		$.get(window.location.href + getQueryCharacter() + "do=propertiesFilter-filter", {data : $("#frm-propertiesFilter-filterForm").serialize()});
	});

	$("#tags-filter").on("change", function () {
		$.get(window.location.href + getQueryCharacter() + "do=tagsFilter-filter", {data : $("#frm-tagsFilter-filterForm").serialize()});
	});
	
	/**
	 * sorters
	 */
	$("#sorter").on("change", function () {
		$.get(window.location.href + getQueryCharacter() + "do=sorter-sort", {data : $("#frm-sorter-sorterForm").serialize()});
	});
	
	/**
	 * init all tooltips
	 */
	$("[data-toggle='tooltip']").tooltip();
	
	/**
	 * search autocomplete function
	 */
	$(".autocomplete" ).autocomplete({
		 minLength: 3,
		 source: function (request, response) {
			$.ajax({
				url: "?do=search-autoComplete",
				data: {
					text: request.term
				},
				success: function( data ) {
					response( data );
				}
			});
		 },
		 focus: function (event, ui) {
			  $(".autocomplete").val(ui.item.name);
			  
			  return false;
		 },
		 select: function(event, ui) {
			 $(".autocomplete").val(ui.item.name);			 
			 $("#search form input[name='id']").val(ui.item.id);
			 $("#search form").submit();
			 
			 return false;
		 }
	})
	.data( "ui-autocomplete" )._renderItem = function( ul, item ) {
		return $( "<li>" )
		.append( "<a class='clearfix'><div class='row'><div class='img col-sm-5'><img src='" + item.image + "' class='img-responsive'></div><div class='col-sm-7'><p class='name h4 no-margin-top'>" + item.name + "</p><p class='price no-margin'>" + item.price + "</p></div></div></a>" )
		.appendTo( ul );
	};
	
	/**
	 * sliding animation on single page web
	 */
	$("[data-spy='scroll'] .nav li a").live('click', function(e) {
	   // prevent default anchor click behavior
	   e.preventDefault();

	   // store hash
	   var hash = this.hash;
	   console.log(hash);
	   // animate
	   $('html, body').animate({
	       scrollTop: $(this.hash).offset().top
	     }, 300, function(){

	       // when done, add hash to url
	       // (default click behaviour)
	       window.location.hash = hash;
	     });
	});
});

function changeUrl (url) {
	window.history.pushState({path : url},'',url);
	
//	$(window).bind('popstate', function() {
//		data[0] = minSelect;
//		data[1] = maxSelect;
//		
//		$.get("?do=priceFilter-filter", {range : data});
//	});
}

function changeMethod (transport) {
	var value = $("#frm-transport tr:eq("+transport+") input:checked").val();
	
	$.get('?do=changeMethod', {'id': value});
}

function changeProperties() {
	$.get("?do=changeProperties", $("#frm-propertiesForm .form-control").serialize());
//	location.href="?do=changeProperties&"+$("#frm-propertiesForm").serialize();
}

function addToCart (form) {
	$.get("?do=cart-addToCart", form);
}

function getQueryCharacter () {
	if (window.location.href.indexOf('?') > 0) {
		return char = '&';
	}
	else {
		return char = '?';
	}
}

function nospam () {
	$(".nospam").hide();
	$(".col-sm-9 > .nospam").parent().parent().hide();
	$("input.nospam").val( "no" + "spam" );
}

$(function() {	
	$("input.datetime").live('focus', function () {
		$(this).datetimepicker({
			dateFormat: "d.m.yy",
			timeFormat: "HH:mm",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
		});
	});	
	
	var val = 0;
	
	$("#cart .add-to-cart input[name='amount']").live("focus", function() {		
		val = $(this).val();
		$(this).val(null);
	});
		
	$("#cart .add-to-cart input[name='amount']").live("blur", function (e) {
		e.preventDefault();
		if ($(this).val() != "" && val != $(this).val()) {
			addToCart ($(this).parents().eq(2).find("input[name!='do']").serialize());
		}
		else {
			$(this).val(val);
		}
	});
	
	$("#cart .add-to-cart input[name='amount']").live("keydown", function(e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			
			if ($(this).val() != "" && val != $(this).val()) {
				addToCart ($(this).parents().eq(2).find("input[name!='do']").serialize());
			}
			else {
				$(this).val(val);
			}
		}
	});
})