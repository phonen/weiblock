function qrCode(){
    $("#wst-dialog5").dialog("show");
}
function inChoice(obj,type){
	$('#type').val(type);
	if(type==0){
		$(obj).addClass('active').siblings('.wst-sha-head .choose').removeClass('active');
		$('#buyer').hide();
		$('#promoter').show();
	}else{
		$(obj).addClass('active').siblings('.wst-sha-head .choose').removeClass('active');
		$('#promoter').hide();
		$('#buyer').show();
	}
	$('#totalPage').val(0);
	$('#currPage').val(0);
	$('#infoList').html('');
	getCommissionList();
}
function getCommissionList(){
	  $('#Load').show();
	  loading = true;
	  var param = {};
	  param.pageSize = 10;
	  param.currPage = Number( $('#currPage').val() ) + 1;
	  param.type = $('#type').val();
	  $.post(ThinkPHP.U('WeChat/DistributMoneys/queryByList'), param, function(data){
	      var json = WST.toJson(data);
	      var mhtml = '';
	      if(json && json.root && json.root.length>0){
	          for(var i=0; i<json.root.length; i++){
	        	  mhtml += '<div class="ui-row-flex wst-sha-com">';
	        	  mhtml += '<div class="ui-col ui-col-2">';
	        	  mhtml += '<p class="info ui-nowrap-multi">'+json.root[i].moneyRemark+'</p>';
	        	  mhtml += '<p>购买金额：¥'+json.root[i].money+'</p>';
	        	  mhtml += '<p class="time">'+json.root[i].createTime+'</p>';
	        	  mhtml += '</div>';
	        	  mhtml += '<div class="ui-col"><p class="money"><span>+</span> ¥'+json.root[i].distributMoney+'</p></div>';
	        	  mhtml += '</div>';
	          }
	          $('#currPage').val(json.currPage);
	          $('#totalPage').val(json.totalPage);
	      }else{
	    	  mhtml += '<ul class="ui-row-flex wst-flexslp">';
	    	  mhtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
	    	  mhtml += '<p>对不起，没有相关信息。</p>';
	    	  mhtml += '</li>';
	    	  mhtml += '</ul>';
	      }
		  $('#infoList').append(mhtml);
	      loading = false;
	      $('#Load').hide();
	  });
}
function getusersList(){
	  $('#Load').show();
	  loading = true;
	  var param = {};
	  param.pageSize = 10;
	  param.currPage = Number( $('#currPage').val() ) + 1;
	  $.post(ThinkPHP.U('WeChat/DistributUsers/queryByList'), param, function(data){
	      var json = WST.toJson(data);
	      var uhtml = '';
	      if(json && json.root && json.root.length>0){
	          for(var i=0; i<json.root.length; i++){
	        	  uhtml += '<div class="ui-row-flex wst-sha-user">';
	        	  if(json.root[i].userPhoto){
	        		  uhtml += '<div class="ui-col"><img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].userPhoto+'"></div>';
	        	  }else{
		        	  uhtml += '<div class="ui-col"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/user_photo.png"></div>';
	        	  }
	        	  uhtml += '<div class="ui-col ui-col-2"><p class="name">'+(json.root[i].userName!=""?json.root[i].userName:json.root[i].loginName)+'</p><p class="time">'+json.root[i].createTime+'</p></div>';
	        	  uhtml += '</div>';
	          }
	          $('#currPage').val(json.currPage);
	          $('#totalPage').val(json.totalPage);
	      }else{
	    	  uhtml += '<ul class="ui-row-flex wst-flexslp">';
	    	  uhtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
	    	  uhtml += '<p>对不起，没有相关信息。</p>';
	    	  uhtml += '</li>';
	    	  uhtml += '</ul>';
	      }
		  $('#userlist').append(uhtml);
	      loading = false;
	      $('#Load').hide();
	  });
}
$(document).ready(function(){
    initFooter('users');
});