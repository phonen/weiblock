//消息列表
function getMessages(){
  $('#Load').show();
  loading = true;
  var param = {};
  param.pageSize = 10;
  param.currPage = Number( $('#currPage').val() ) + 1;
  $.post(ThinkPHP.U('WeChat/Messages/getMessagesList'), param, function(data){
      var json = WST.toJson(data);
      var mhtml = '';
      var phtml = '';
      if(json && json.root && json.root.length>0){
          for(var i=0; i<json.root.length; i++){
        	  mhtml += '<li>';
        	  mhtml += '<div class="ui-list-info" onclick="javascript:getMsgDetails('+json.root[i].id+');">';
        	  mhtml += '<h5 class="ui-nowrap">';
        	  mhtml += '<span class="';
              if( json.root[i].msgStatus == 0 ){
            	  mhtml += 'wst-info_ico';
              }else{
            	  mhtml += 'wst-info_ico1';
              }
              mhtml += '"></span>'+json.root[i].msgContent+'</h5>';
        	  mhtml += '</div>';
        	  mhtml += '<label class="ui-checkbox">';
              mhtml += '<input class="active" type="checkbox" msgId="'+json.root[i].id+'" onclick="javascript:changeIconStatus($(this), 1);">';
              mhtml += '</label></li>';
              mhtml += '<div class="wst-Line"></div>';
          }
          $('#currPage').val(json.currPage);
          $('#totalPage').val(json.totalPage);
          $('#info-list').append(mhtml);
      }else{
		  phtml += '<ul class="ui-row-flex wst-flexslp">';
		  phtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
		  phtml += '<p>对不起，没有相关消息。</p>';
		  phtml += '</li>';
		  phtml += '</ul>';
		  $('.info-prompt').append(phtml);
    }
      loading = false;
      $('#Load').hide();
  });
}
//返回消息列表
function returnInfo(){
	WST.showHide('','.return_info,.info_details,#info_details');
	WST.showHide(1,'.info_list,#info_list,.return_users,#footer');
	$('body').css('background','#f9f9f9');
}
//消息详情
function getMsgDetails(id){
	WST.showHide('','.info_list,#info_list,.return_users,#footer');
	WST.showHide(1,'.return_info,.info_details,#info_details');
	$('body').css('background','white');
    $.post(ThinkPHP.U('WeChat/Messages/getMsgDetails'), {id:id}, function(data){
        var json = WST.toJson(data);
        $('.wst-info_detime').html(json.createTime);
        $('.wst-info_decontent').html(json.msgContent);
        json = null;
    });
}
var msgIdsToDel = '';//要删除的消息的id串
//去删除商城消息
function toDelMsg(){
  var msgCount = $('.ui-icon-unchecked-s').length;//消息个数
  msgIdsToDel = '';
  for(var i=0; i<msgCount; i++){
      msgIdsToDel += $('.ui-icon-unchecked-s').eq(i).attr('msgId');
      if(i!=msgCount-1){
          msgIdsToDel +=  ',';
      }
  }
  if( msgCount == 0 || msgIdsToDel == '' ){
	  WST.msg('请选择要删除的消息','info');
	  return false;
  }
  WST.dialog('确定要删除选中的消息吗？','delMsg()');
}
var vn ='wca926fa4b458b2179d8872b3acd1d501';
//删除商城消息
function delMsg(){
  $.post(ThinkPHP.U('WeChat/Messages/delMsg'), {ids:msgIdsToDel}, function(data){
      var json = WST.toJson(data);
      if(json.status==1){
    	  WST.msg('删除成功','success');
          setTimeout(function(){
              location.href = ThinkPHP.U('WeChat/Messages/index');
          },2000);
      }else{
    	  WST.msg('删除失败，请重试','warn');
      }
      WST.dialogHide(1);
  });
}
$(document).ready(function(){
    getMessages();
    var currPage = totalPage = 0;
    var loading = false;
    $(window).scroll(function(){ 
        if (loading) return;
        if ((5 + $(window).scrollTop()) >= ($(document).height() - $(window).height())){
            currPage = Number( $('#currPage').val() );
            totalPage = Number( $('#totalPage').val() );
            if( totalPage > 0 && currPage < totalPage ){
              getMessages();
            }
        }
    });
});