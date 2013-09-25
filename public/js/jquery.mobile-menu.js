
var mobileApp = mobileApp || {};

;(function(mobileApp,$){
  var MobileMenu = function(elem, options){
    this.elem = elem;
    this.$elem = $(elem);
    this.options = options;
  };

  MobileMenu.prototype = {
    defaults: {
      page_id: 'build-menu-page',
      menu: '', 
      menu_width: 260,
      menu_id: "mobile-nav",
      button_content: '<div id="nav-button"></div>',
      prepend_button_to: '',
      menu_bar: ''
    },

    init: function(){
      var _this = this;
      _this.config = $.extend({}, _this.defaults, _this.options);

      if($(_this.config.menu_id).length === 0){
        this.setLayout();
      }

      return _this;
    },

    buildMenu: function(){
      var _this = this;
      _this.config = $.extend({}, _this.defaults, _this.options);
      var menu = _this.config.menu,
          mobile_menu = $("#build-menu"),
          menu_collection = [];

      if(menu){
        if($.isArray(menu)){
          $(menu).each(function(i, e){
            mobile_menu.append($(e).clone().addClass(_this.config.menu_id+"-"+i));
            $(e).hide();
          });
        } else {
          mobile_menu.append($(menu).clone().addClass(_this.config.menu_id+"-0").removeAttr("id"));
          $(menu).hide();
        }
      } else {
        return false;
      }
    },

    setCSS: function(){
      var _this = this;
      _this.config = $.extend({}, _this.defaults, _this.options);

      $("#build-menu-overlay").css({
        position: "absolute",
        top: 0,
        bottom: 0,
        right: 0,
        left: 0,
        "z-index": 99,
        background: "#000",
        opacity: 0.5,
        display: "none"
      });

      $("html,body").css("height","100%");

      if(_this.config.menu_bar){
        $(_this.config.menu_bar).css({
          position: "fixed"
        });
      }

    },

    setLayout: function(){
      var _this = this;
          _this.config = $.extend({}, _this.defaults, _this.options);

      if(_this.config.prepend_button_to == ''){
        var prepend_button_to = "#"+_this.config.page_id;
      } else {
        var prepend_button_to = _this.config.prepend_button_to;
      }

      _this.$elem.wrapInner('<div id="'+_this.config.page_id+'" />').find("#"+_this.config.page_id).before('<div id="build-menu" />');
      $(prepend_button_to).prepend('<a href="#" id="build-menu-button">'+_this.config.button_content+'</a>');
      $("#"+_this.config.page_id).prepend('<div id="build-menu-overlay" />');

      this.buildMenu();
      this.setCSS();

      var element = document.getElementById(_this.config.page_id);
      element.addEventListener("oTransitionEnd", remove_animation_class,false);
      element.addEventListener("transitionend", remove_animation_class,false);
      element.addEventListener("webkitTransitionEnd", remove_animation_class,false);
      element.addEventListener("MSTransitionEnd", remove_animation_class,false);

      function remove_animation_class(){
        if($("html").hasClass("build-menu-close")){
          $("html").removeClass("build-menu-animating");
        }
      }

      $("#build-menu-button, #build-menu-overlay").on("click", function(e){
        e.preventDefault();
        var html = $("html");
        var page = $("#"+_this.config.page_id);
        var overlay = $("#build-menu-overlay");

        html.addClass("build-menu-animating");

        if(html.hasClass("build-menu-open")){
          html.removeClass("build-menu-open");
          html.addClass("build-menu-close");

          page.css({
            "-webkit-transform": "translateX(0px)",
            "-moz-transform": "translateX(0px)",
            "-o-transform": "translateX(0px)",
            "-ms-transform": "translateX(0px)",
            "transform": "translateX(0px)"
          });

          overlay.fadeTo("slow",0, function(){
            $(this).css("visibility", "hidden");
          });
        } else {
          html.addClass("build-menu-open");
          html.removeClass("build-menu-close");

          page.css({
            "-webkit-transform": "translateX("+_this.config.menu_width+"px"+")",
            "-moz-transform": "translateX("+_this.config.menu_width+"px"+")",
            "-o-transform": "translateX("+_this.config.menu_width+"px"+")",
            "-ms-transform": "translateX("+_this.config.menu_width+"px"+")",
            "transform": "translateX("+_this.config.menu_width+"px"+")"
          });

        }
      });
    }
  };

  MobileMenu.defaults = MobileMenu.prototype.defaults;

  $.fn.mobile_menu = function(options) {
    return this.each(function() {
      new MobileMenu(this, options).init();
    });
  };

  mobileApp.MobileMenu = MobileMenu;
})(mobileApp,jQuery);