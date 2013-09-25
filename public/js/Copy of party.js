String.prototype.len=function(){return this.replace(/[^\x00-\xff]/g,"aa").length;};
String.prototype.trim=function(){return this.replace(/(^ *)|( *$)/g, "");};
String.prototype.encode=function(){return this.replace(/·/g," ").replace(/\+/g,"%2B");};
String.prototype.left=function(length){
  if(this.len()>length){
      var _temp=this;
      _temp=_temp.replace(/([^\x00-\xff])/g,"$1>");
      _temp=_temp.substring(0,length-2)+"..";
      return _temp.replace(/>/g,"");
  }else{
      return this.toString();
  }
};
Element.prototype.show=function(){this.style.display="block";};
Element.prototype.hide=function(){this.style.display="none";};
Element.prototype.center=function(top){
   this.style.left=(_system._scroll().x+_system._zero(_system._client().bw-this.offsetWidth)/2)+"px";
   this.style.top=(top?top:(_system._scroll().y+_system._zero(_system._client().bh-this.offsetHeight)/2))+"px";
};
var _system={
   _client:function(){
      return {w:document.documentElement.scrollWidth,h:document.documentElement.scrollHeight,bw:document.documentElement.clientWidth,bh:document.documentElement.clientHeight};
   },
   _scroll:function(){
      return {x:document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft,y:document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop};
   },
   _cover:function(show){
      if(show){
	     $("cover").show();
	     $("cover").style.width=(this._client().bw>this._client().w?this._client().bw:this._client().w)+"px";
	     $("cover").style.height=(this._client().bh>this._client().h?this._client().bh:this._client().h)+"px";
	  }else{
	     $("cover").hide();
	  }
   },
   _loading:function(text){
      if(text){
         this._cover(true);
         $("loading").show();
		 $("loading_text").innerHTML=text;
		 $("loading").center();
		 window.onresize=function(){_system._cover(true);$("loading").center();};
	  }else{
         this._cover(false);
         $("loading").hide();
		 window.onresize=null;
	  }
   },
   _toast:function(text,fun){
      $("toast").show();
      $("toast").innerHTML=text;
      $("toast").center();
      setTimeout(function(){
	     $("toast").hide();
		 if(fun){(fun)();}
      },3*1000);
   },
   _ok:function(text,fun){
      $("ok").show();
      $("ok_text").innerHTML=text;
      $("ok").center();
	  window.onresize=function(){$("ok").center();};
      setTimeout(function(){
		 window.onresize=null;
	     $("ok").hide();
         (fun)();
      },2*1000);
   },
   _guide:function(click){
      this._cover(true);
      $("guide").show();
	  $("guide").style.top=(_system._scroll().y+5)+"px";
      window.onresize=function(){_system._cover(true);$("guide").style.top=(_system._scroll().y+5)+"px";};
	  if(click){$("cover").onclick=function(){
         _system._cover();
         $("guide").hide();
		 $("cover").onclick=null;
		 window.onresize=null;
	  };}
   },
   _zero:function(n){
      return n<0?0:n;
   },
   _forbidden:function(text){
      return text.match(/(老市长|薄熙来|薄市长|法轮功)/)!=null;
   },
   _shareUrl:function(){
	  //var domain=["veigou.com","www.veigou.com"],path=["detail.jsp?r=","show.asp?l=","index.do?u=","info.do?i="];
	  var domain=["login.veigou.com"],path=["s/party/share?s=","s/party/share?s=","s/party/share?s=","s/party/share?s="];
      return "http://login.veigou.com/"+path[parseInt(path.length*Math.random())]+dataForWeixin.path;
   }
};
(function(){
   var onBridgeReady=function(){
   WeixinJSBridge.on('menu:share:appmessage', function(argv){
      WeixinJSBridge.invoke('sendAppMessage',{
         "appid":dataForWeixin.appId,
         "img_url":dataForWeixin.MsgImg,
         "img_width":"120",
         "img_height":"120",
         "link":_system._shareUrl(),
         "desc":dataForWeixin.desc,
         "title":dataForWeixin.title
      }, function(res){(dataForWeixin.callback)();});
   });
   WeixinJSBridge.on('menu:share:timeline', function(argv){
	  (dataForWeixin.callback)();
	  WeixinJSBridge.invoke('shareTimeline',{
         "img_url":dataForWeixin.TLImg,
         "img_width":"120",
         "img_height":"120",
         "link":_system._shareUrl(),
         "desc":dataForWeixin.desc,
         "title":dataForWeixin.title
      }, function(res){});
   });
   WeixinJSBridge.on('menu:share:weibo', function(argv){
	  WeixinJSBridge.invoke('shareWeibo',{
         "content":dataForWeixin.title,
         "url":_system._shareUrl()
      }, function(res){(dataForWeixin.callback)();});
   });
   WeixinJSBridge.on('menu:share:facebook', function(argv){
	  (dataForWeixin.callback)();
	  WeixinJSBridge.invoke('shareFB',{
         "img_url":dataForWeixin.TLImg,
         "img_width":"120",
         "img_height":"120",
         "link":_system._shareUrl(),
         "desc":dataForWeixin.desc,
         "title":dataForWeixin.title
      }, function(res){});
   });
};
if(document.addEventListener){
   document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
}else if(document.attachEvent){
   document.attachEvent('WeixinJSBridgeReady'   , onBridgeReady);
   document.attachEvent('onWeixinJSBridgeReady' , onBridgeReady);
}
})();
var _$=function(url,parameters,loadingMessage,functionName){
    var request=new XMLHttpRequest();
    if(loadingMessage!=""){_system._loading(loadingMessage);}
    var method="POST";
    if(parameters==""){method="GET";parameters=null;}
    request.open(method,url,true);
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    request.onreadystatechange=function(){
	 if(request.readyState==4){
         if(loadingMessage != ""){_system._loading();}
	     if(request.status==200){
		    if(functionName){
		       try{
			      var json = eval("("+ request.responseText+")");
			      eval(functionName+"(json)");
                }catch(e){}
		    }
	     }else{
	         if(loadingMessage != ""){_system._toast("发生意外错误，请稍候再试");}
	     }
	 }
    };
    request.send(parameters);
};