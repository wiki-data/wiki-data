var API = {
  query: function(args, cb) {
    $.post('/w/api.php?format=json&action=query', args, cb);
  },
  get: function(name,args,cb,reload) {
    var hash = name + JSON.stringify(args);
    var me = this;
    if (reload || !me.data[hash]) {
      if (me.waiting[hash]) {
        me.waiting[hash].push(cb);
      } else {
        me.waiting[hash]=[cb];
        me.queries[name].apply(me,[args,function(data) {
          me.data[hash] = data;
          for (var i=0; i< me.waiting[hash].length; i++ ) {
            me.waiting[hash][i].apply(me,[data]);
          }
          delete me.waiting[hash];
        }]);
      }
    } else {
      cb(this.data[hash]);
    }
  },
  waiting: {},
  data: {},
  iconPath: '/w/static/icons/sticker/',
  icons: {
    _: 'document-file',
    '-1': 'wrench', //special
    0:'document',
    1:'balloon', // talk
    2:'user',
    4:'document-file',//project
    6:'image', //file
    8:'gears',//mediawiki
    10:'puzzle',//template
    12:'question-button',//help
    14:'documents',//category
    1242:'database',//data/
    hiddencat: 'folder'
  },
  queries: {
    page: function(args,cb) {
      var me = this;
      this.query({
        titles: args.title,
        prop:'info|revisions|links|iwlinks|langlinks|images|imageinfo|templates|categories|extlinks|categoryinfo|pageprops',
        clprop: 'hidden|sortkey|timestamp',
        list:'backlinks|embeddedin',
        bltitle:args.title,
        eititle:args.title,
        imlimit:50,
        pllimit:50,
        tllimit:50,
        cllimit:50,
        bllimit:50,
        eilimit:50,
        rvlimit:20
      }, function(res) {
        if (!res.query) {
          cb([]);
          return;
        }
        var ret = {};
        ret.backlinks = me.parse.pages(res.query.backlinks);
        ret.transclusions = me.parse.pages(res.query.embeddedin);
        for (var i in res.query.pages) {
          var p = res.query.pages[i];
          ret.links = me.parse.pages(p.links);
          ret.revisions = me.parse.revisions(p.revisions,p.title);
          ret.templates = me.parse.pages(p.templates);
          ret.categories = me.parse.categories(p.categories);
          ret.hiddencat = me.parse.hiddencat(p.categories);
        }
        cb(ret);
      });
    },
    images: function(args,cb) {
      var me = this;
      this.query({
          prop:'imageinfo',
          iiprop:'url|user|size|timestamp',
          iiurlwidth:64,
          iiurlheight:64,
          generator:'images',
          gimlimit:'50',
          titles:args.title,
        }, function(res) {
        if (!res.query) {
          cb([]);
          return;
        }
        var ret = {};
        ret.images = me.parse.imageinfo(res.query.pages);
        console.log(res);
        cb(ret);
      });
    },
    uploads: function(args,cb) {
      var me = this;
      this.query({
        action:'query',
        list:'logevents',
        letype:'upload',
        leprop:'title',
        lelimit:20,
      }, function(res) {
        console.log('res',res);
        if (!res.query) {
          cb([]);
          return;
        }
        var titles = []; for (var i in res.query.logevents) titles.push(res.query.logevents[i].title);
        API.get('imageinfo',{titles:titles},cb);
      });
    },
    imageinfo: function(args,cb) {
      var me = this;
      this.query({
        action:'query',
        prop:'imageinfo',
        iiprop:'url|user|size|timestamp',
        iiurlwidth:64,
        iiurlheight:64,
        iilimit:50,
        titles:args.titles.join('|')
      }, function(res) {
        if (!res.query) {
          cb([]);
          return;
        }
        var ret = {};
        ret.imageinfo = me.parse.imageinfo(res.query.pages);
        cb(ret);
      });
    },
    recentchanges: function(args,cb) {
      var me = this;
      this.query({
        prop:'info',
        list:'recentchanges',
        rclimit:20,
        rcprop:'ids|title|loginfo|timestamp|user',
        rctype:args.type,
        rctoponly:true,
        generator:'recentchanges',
        grclimit:20,
        grctype:args.type,
        grctoponly:true
      }, function(res) {
        if (!res.query) {
          cb([]);
          return;
        }
        var ret = {};
        for (var i in res.query.recentchanges) {
          res.query.recentchanges[i].page = res.query.pages[res.query.recentchanges[i].pageid];
        }
        ret.changes = me.parse.changes(res.query.recentchanges);
        cb(ret);
      });
    }
  },
  parse: {
    page: function(p) {
      var r = {};
      r.name = p.ns ? p.title.replace(/^[^:]+:/,'') : p.title;
      r.image = API.iconPath + (API.icons[p.ns] || API.icons._)+'.png';
      r.title = p.title;
      r.url = (wiki.wgArticlePath || '/$1').replace(/\$1/,encodeURI(p.title));
      return r;
    },
    pages: function(pp) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        var r = API.parse.page(p);
        r.details = {
          title: p.title,
          namespace: 0|p.ns
        }
        ret.push(r);
      }
      return ret;
    },
    changes: function(pp) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        var r = API.parse.page(p);
        r.details = {
          user:p.user,
          timestamp:p.timestamp,
        }
        ret.push(r);
      }
      return ret;
    },
    revisions: function(pp,title) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        p.title = title;
        var r = API.parse.page(p);
        r.name = timef(p.timestamp);
        r.url += '/w/index.php?title='+encodeURI(title)+'&oldid=' + p.revid;
        r.details = {
          user:p.user,
          comment:p.comment
        }
        ret.push(r);
      }
      return ret;
    },
    categories: function(pp) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        if ('hidden' in p) continue;
        var r = API.parse.page(p);
        r.details = {
          sortkey: p.sortkeyprefix,
          timestamp: p.timestamp
        }
        ret.push(r);
      }
      return ret;
    },
    hiddencat: function(pp) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        if (!('hidden' in p)) continue;
        var r = API.parse.page(p);
        r.details = {
          sortkey: p.sortkeyprefix,
          timestamp: p.timestamp
        }
        ret.push(r);
      }
      return ret;
    },
    imageinfo: function(pp) {
      var ret = [];
      for(var i in pp) {
        var p = pp[i];
        var ii = p.imageinfo ? p.imageinfo[0] : null;
        var r = API.parse.page(p);
        r.image = ii && ii.thumburl ? ii.thumburl : r.image;
        r.details = ii ? {
          W: ii.width,
          H: ii.height,
          size: ii.size
        } : {}
        ret.push(r);
      }
      return ret;
    }
  }
}

function timef(t,f) {
  f = f || 'Y-m-d h:i';
  var d = t ? new Date(t) : new Date();
  function n(n) {
    return n<10 ? '0'+n : n ;
  }
  var out = '';
  for (var i = 0; i<f.length; i++) {
    switch(f[i]) {
    case 'Y':
      out+= d.getYear()+1900;
      break;
    case 'y':
      out+= (d.getYear()+1900)%100;
      break;
    case 'm':
      out+= n(d.getMonth()+1);
      break;
    case 'd':
      out+= n(d.getDate());
      break;
    case 'h':
      out+=n(d.getHours());
      break;
    case 'i':
      out+=n(d.getMinutes());
      break;
    case 's':
      out+=n(d.getSeconds());
      break;
    default:
      out+=f[i];
    }
  }
  return out;
}


function CustomTools(){


var $ = jQuery;

jQuery.fn.showtip = function(text,duration) {
  var $wrap = $('<span style="position:relative;"></span>');
  var $pos = $('<div style="position:absolute;bottom:100%;left: 50%;"></div>').appendTo($wrap);
  var $tip = $('<div style="display:inline-block;margin-left:-50%;background: white;padding: 4px;text-align: center;color:black;border: solid 1px silver;border-radius: 3px;line-height: 12px;width: auto;">'+text+'</div>').appendTo($pos);
  $wrap.insertBefore(this).append(this);
  var me = this;
  $tip.animate({opacity:1},duration||5000).animate({opacity:0},1000,function(){
    $wrap.before(me).remove();
  });
}



var customTools = {
  setup: function () {
    
    var width = 500; //Math.max(300,Math.min(parseInt(window.localStorage.sidebarWidth),1000));  

    this.$page = 
    $('<div class="tools-top" id="tools-top"></div>');
    $('body').html(this.$page);

    this.$main = 
    $('<div id="tools-main"></div>')
    .appendTo(this.$page);
    
    var $base = $('head base');
    if ($base.length) $base.attr('target','wiki');
    else $('head').append('<base target="wiki"/>');

    var sep = window.location.search ? '&' : '?';

    this.$wiki = $('<iframe style="width:100%;height:100%" name="wiki" id="wiki" src="'+window.location.href.split('#')[0]+sep+'frame=inside"></iframe>')
    .appendTo(this.$main);
    
    this.$sidebar = 
    $('<div class="tools-sidebar" id="tools-sidebar"></div>')
    .appendTo(this.$page);
    this.dock = new customTabs('tools-desktop','tools-dock','tools-button');
    this.$sidebar.append(this.dock.$element);
    this.setWidth(width);
  },
  setWidth:function (w) {
    width = Math.max(200,Math.min(w,1000));
    this.$sidebar.width(width);
    this.$main.css('left',width);
    window.localStorage.sidebarWidth = width;
  }
}
function customTabs (topClass,tabsClass,tabClass) {
  this.topClass = topClass || 'tools-tabbook';
  this.tabsClass = tabsClass || 'tools-tabs';
  this.tabClass = tabClass || 'tools-tab';
  this.$element = 
  $('<div ></div>')
  .addClass(this.topClass);
  
  this.$tabs = 
  $('<div ></div>')
  .addClass(this.tabsClass)
  .appendTo(this.$element);
  this.$panes = 
  $('<div class="tools-panes"></div>')
  .appendTo(this.$element);
  this.panes = {};
}

customTabs.prototype = {
  register: function(pane) {
    var me = this;
    this.panes[pane.name] = pane;
    pane.$tab = 
    $('<span>'+pane.name+'</span>')
    .click(function() {
      me.show(pane);
    })
    .addClass(this.tabClass)
    .appendTo(this.$tabs);
    if (!this.currentPane) this.show(pane);
  },
  show: function (pane) {
    this.currentPane && this.hide(this.currentPane);
    this.currentPane = pane;
    if (!pane.isOpen) this.open(pane);
    pane.$tab.addClass('selected');
    pane.$pane.addClass('selected');
    pane.onShow && pane.onShow.apply(pane,[this]);
  },
  open: function (pane) {
    pane.$pane = $('<div class="tools-pane"></div>')
    .appendTo(this.$panes);

    pane.onOpen && pane.onOpen.apply(pane,[this]);
    pane.isOpen = true;
    
    pane.$element = $(pane.element);
    
    pane.$pane
    .append(pane.$element);
  },
  hide: function (pane) {
    pane.onHide && pane.onHide.apply(pane,[this]);
    pane.$tab.removeClass('selected');
    pane.$pane.removeClass('selected');
  },
  close: function (pane) {
    this.hide(currentPane);
    pane.onClose && pane.onClose.apply(pane,[this]);
    pane.$element.remove();
    pane.isOpen = false;
  },
  refresh: function() {
    for (var i in this.panes) this.panes[i].isOpen && this.panes[i].refresh && this.panes[i].refresh();
  }
}
customTools.setup();


var customItem = function(list,opt) {
  opt = opt || {};
  this.list = list;
  this.url = opt.url;
  this.name = opt.name || 'unnamed';
  this.image = opt.image || list.defaultImage;
  this.details = opt.details || {};
  this.actions = opt.actions || {};
  for (var i in this.list.defaultActions) {
    this.actions[i] = this.list.defaultActions[i];
  }
  this.element = $('<li class="tools-item">'
    + '<span class="tools-item-column tools-item-image">'
    + '<img src="' + this.image + '">'
    + '</span>'
    + '<span class="tools-item-column tools-item-name">' 
    + (this.url ? '<a href="'+this.url+'">'+this.name+'</a>' : this.name)
    + '</span>'
    + '</li>'
  );
  for (var i in this.details) {
    this.element.append('<span class="tools-item-column tools-item-detail tools-item-detail-'+i+'">' + this.format(i,this.details[i]) + '</span>');
  }
  this.element.appendTo(this.list.$list);
}

customItem.prototype = {
  format: function(name,val) {
    return this.formats[name] ? this.formats[name].apply(this,[val]) : val;
  },
  formats: {
    timestamp: function(val) {
    	var now = new Date();
    	var d = new Date(val);
    	var r = 0;
      if ( d.getYear() != now.getYear()) r = timef(val,'d.m.y h:i');
      else if (d.getMonth() != now.getMonth() || d.getDate() != now.getDate()) r = timef(val,'d.m. h:i');
      else r = timef(val,'h:i');
      return '<div style="text-align:right">'+r+'</div>';
    },
    user: function(val) {
      return '<a href="/User:'+encodeURI(val)+'">'+val+'</a>';
    }
  }
}

var customList = function(opt) {
  opt = opt||{};
  this.name = opt.name || 'unnamed';
  this.defaultView = opt.defaultView || 'list';
  this.views = opt.views || ['list','icons','tiles','details'];
  
  this.getItems = opt.getItems || function(cb) { cb.apply(this,[opt.items||[]]); }
  this.element = $('<dl class="tools-list"></dl>');
  this.$head = $('<dt class="tools-heading">'+this.name+'</dt>').appendTo(this.element);
  this.$views = $('<span class="tools-heading-bar"> </span>').appendTo(this.$head);
  this.element.addClass('tools-list-'+this.defaultView);
  
  var me = this;
  this.views.forEach(function(v) {
    var $button =  $('<span class="tools-button">'+v+'</span>')
    .click( function(){
      console.log(v);
      me.$views.find('.selected').removeClass('selected');
      $(this).addClass('selected');
      me.element.attr('class','tools-list tools-list-'+v);
      me.view = v;
    })
    .appendTo(me.$views);
    if (v == me.defaultView || ! me.defaultView ) $button.click();
  })
  
  this.$body = $('<dd></dd>').appendTo(this.element);
  this.$list = $('<ul class="tools-list-body"></ul>').appendTo(this.$body);

}

customList.prototype = {
  refresh: function() {
    this.items = [];
    var me = this;
    this.getItems(function(items) {
      var details = {};
      for (var i in items) for (var j in items[i].details) details[j]=true;
      var $header = $('<tr class="tools-list-header"><th style="text-align:center"></th><th>name</th></tr>');
      for (var i in details) $header.append('<th>'+i+'</th>')
      me.$list.html($header);
      for (var i in items) {
        var d = {};
        for (var j in details) d[j]=items[i].details[j] || '';
        items[i].details = d;
        var item = new customItem(me,items[i]);
        me.items.push(item);
      }
    })
  }
}

var customPreview = {
  image: function (view, title) {
    $.post('/w/api.php?format=json', {
      action:'query',
      prop:'imageinfo',
      iiprop:'timestamp|user|url|size|mediatype|metadata|bitdepth',
      titles: title,
      iiurlwidth:180,
      iiurlheight:180,
      iilimit:3
    }, function(data) {
      for (var i in data.query.pages) {
        var pp = data.query.pages[i];
        var iii = pp.imageinfo[0]
        $preview.html('<img src='+iii.thumburl+'>');
        $('<li><a target="wiki" href="'+iii.descriptionurl+'">'+pp.title+'</a></li>').appendTo($info);
        var $dl = $('<dl></dl>').appendTo($info);
        var $dt = $('<dt>versions:</dt>').appendTo($dl);
        var $dd = $('<dd></dd>').appendTo($dl);
        var $ul = $('<ul></ul>').appendTo($dd);
        $('<li>'+timef(iii.timestamp)+' <a target="wiki" href="/User:'+iii.user+'">'+iii.user+'</a></li>').appendTo($ul);
        $('<li><small>'+iii.width+'×'+iii.height+' ('+iii.size+' bytes)</small></li>').appendTo($ul);
        var $usage = $('<li></li>').appendTo($info);
      }
      $.get('/w/api.php?format=json', {
        action:'query',
        prop:'info',
        inprop:'url',
        generator:'imageusage',
        giutitle:title
      }, function(data) {
        if(!data.query) return;
        var $dl = $('<dl></dl>').appendTo($info);
        var $dt = $('<dt>usage:</dt>').appendTo($dl);
        var $dd = $('<dd></dd>').appendTo($dl);
        var $ul = $('<ul></ul>').appendTo($dd);
        for (var i in data.query.pages) {
          var pp = data.query.pages[i];
          $('<li><a target="wiki" href="'+pp.fullurl+'">'+pp.title+'</a></li>').appendTo($ul);
        }
      },'json');
    });
  }
}
var customFormat = {
  title: function(t) {
    return '<a target="wiki" href="/'+encodeURI(t)+'">'+t+'</a>';
  },
  user: function(t) {
    return '<a target="wiki" href="/User:'+encodeURI(t)+'">'+t+'</a>';
  }
}
var customView = function(opt) {
  opt = opt||{};
  this.name = opt.name || 'unnamed';
  this.lists = {};
  this.setup = opt.setup;
  this.autoload = opt.autoload === undefined ? true : opt.autoload;
  for (var i in opt.lists) {
    this.lists[i] = new customList(opt.lists[i]);
  }
  this.element=$('<div></div>');
}



customView.prototype = {
  onOpen: function () {
    this.$view = $('<div class="tools-view"></div>').appendTo(this.element);
    this.$lists = $('<div class="tools-lists"></div>').appendTo(this.element);
    this.$preview = $('<div class="tools-view-preview"><i><small>(nothing selected)</small></i></div>').appendTo(this.$view);
    this.$info = $('<ul class="tools-view-info"><ul>').appendTo(this.$view);
    for (var i in this.lists) {
      this.$lists.append(this.lists[i].element);
    }
    this.setup && this.setup();
    if(this.autoload) this.refresh();
  },
  refresh: function() {
    for (var i in this.lists) {
      this.lists[i].refresh();
    }  
  }
}

customTools.dock.register ({
  name:"page",
  autoload: false,
  onOpen: function() {
    var me = this;
    $('#wiki').load(function() {
      console.log('re');
      me.tabs.refresh();
    })
    this.loaded = false;
    this.tabs = new customTabs();
    this.element = this.tabs.$element;
    
    this.tabs.register(new customView({
      name:'content',
      lists: {
        files: {
          name: 'images',
          getItems: function(cb) {
            API.get('images',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.images);
            },true)
          },
        },
        categories: {
          name: 'categories',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.categories);
            },true)
          },
          views: ['list','details']
        },
        hiddencat: {
          name: 'hidden categories',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.hiddencat);
            },true)
          },
          views: ['list','details']
        },
        links: {
          name: 'links',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.links);
            },true)
          },
          views: ['list']
        },
        backlinks: {
          name: 'what links here',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.backlinks);
            },true)
          },
          views: ['list']
        },
      }
    }));
    this.tabs.register(new customView({
      name:'templates',
      lists: {
        templates: {
          name: 'templates',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.templates);
            },true)
          },
          views: ['list']
        },
        transclusions: {
          name: 'transclusions',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.transclusions);
            },true)
          },
          views: ['list']
        },
      }
    }));
    this.tabs.register(new customView({
      name:'revisions',
      lists: {
        templates: {
          name: 'revisions',
          getItems: function(cb) {
            API.get('page',{title:wiki.wgCanonicalNamespace + ':' +wiki.wgTitle},function(data) {
              cb(data.revisions);
            },true)
          },
          defaultView:'details',
          views: []
        }
      }
    }));
  }
});


customTools.dock.register (
  new customView({
    name:"latest",
    lists: {
      edits: {
        name: 'recent edits',
        getItems: function(cb) {
          API.get('recentchanges',{type:'edit'},function(data) {
            cb(data.changes);
          },true)
        },
        views: ['list','details']
      },
      newpages: {
        name: 'new pages',
        getItems: function(cb) {
          API.get('recentchanges',{type:'new'},function(data) {
            cb(data.changes);
          },true)
        },
        views: ['list','details']
      },
      images: {
        name: 'latest images',
        getItems: function(cb) {
          API.get('uploads',{},function(data) {
            cb(data.imageinfo);
          },true)
        },
        views: ['list','tiles','details']
      }
    },
  })
);

customTools.dock.register({
  name: 'css',
  _getSheets: function (sheet,sheets) {
    sheets.push(sheet);
    for (var i = 0; i<sheet.cssRules.length; i++) {
      var s = sheet.cssRules.item(i);
      if (s.type==3) this._getSheets(s.styleSheet,sheets);
    }
  },  
  getSheets: function (doc) {
    var sheets = [];
    for (var i=0; i < doc.styleSheets.length; i++) {
      this._getSheets(doc.styleSheets.item(i),sheets);
    }
    return sheets;
  },
  removeSheets: function (doc,sheets) {
    for (var i=0; i<sheets.length;i++) {
      $(sheets[i].ownerNode).remove();
    }
  },
  makeBoxes: function (doc,sheets) {
    var boxes = [];
    for (var i=0; i<sheets.length;i++) {
      var box = this.makeBox(doc,sheets[i]);
      if(!box) continue;
      boxes.push(box);
      this.tabs.register(box);
    }
    return boxes;
  },
  setupBox: function (doc,box) {
      box.$style = $('<style>' + box.$text.val() + '</style>').appendTo($('head',doc));
      box.$style.html(box.$text.val());
  },
  setupBoxes: function(doc,boxes) {
    for (var i = 0; i<boxes.length; i++) {
      this.setupBox(doc,boxes[i]);
    }
  },
  onOpen: function() {
    this.loaded = false;
    this.boxes = [];
    this.tabs = new customTabs();
    this.element = this.tabs.$element;
    var me = this;
    $('#wiki').load(function() {
      me.onLoad();
    });
    this.onLoad();
  },
  onLoad: function() {
    var doc = wiki.document;
    var sheets = this.getSheets(doc);
    if(!this.loaded) this.boxes = this.makeBoxes(doc,sheets);
    this.removeSheets(doc,sheets);
    if (this.loaded) this.setupBoxes(doc,this.boxes);
    this.loaded = true;
  },
  makeBox:function(doc,sheet) {
    var msg = sheet.href && (sheet.href.match( /css[/]([^?]*)/ ) || sheet.href.match(/Mediawiki:(.*?)\.css/i));
    var box = {
      doc:doc,
      sheet:sheet,
      msg:msg ? msg[1] : null,
      name:msg ? msg[1] : sheet.href
    };
/******/
    if (!box.msg) return;
/******/
    var me = this;
    box.element = $('<div></div>');
    
    
    $.get(sheet.href, function(data) {
      var T = null;
      var oldtext = data;
      
      box.$textwrap= 
      $('<div class="tools-max-withfooter"></div>')
      .appendTo(box.element);

      box.$text =
      $('<textarea class="tools-max">'+data+'</textarea>')
      .keyup(function() {
        clearTimeout(T);
        T = setTimeout(function() {
          box.$tab.text('*'+box.name);
          box.$style.html(box.$text.val());
          box.$save && box.$save.removeAttr('disabled');
          box.$revert.removeAttr('disabled');
        }, 1000)
      })
      .appendTo(box.$textwrap);

      
      box.$bar = 
      $('<div class="tools-footer"></div>')
      .appendTo(box.element);
      box.$revert =
      $('<span class="tools-button" id="tools--css-revert">revert</span>')
      .click(function() {
        $.get(sheet.href, function(data) {
          box.$text.val(data);
          box.$tab.text(box.name);
          box.$style.html(box.$text.val());
          box.$revert.attr('disabled','disabled')
          box.$save && box.$save.attr('disabled','disabled');
        });
      })
      .appendTo(box.$bar);
      if (box.msg) {
        box.$save =
        $('<span class="tools-button" id="tools--css-save">save</span>')
        .click(function() {
          box.$save.attr('disabled','disabled');
	        $.get('/w/api.php?format=json&action=query&meta=userinfo',function(data){
		        var user = data.query.userinfo.name;
		        var title = 'MediaWiki:'+box.msg+'.css';
		        $.get('/w/api.php?format=json&action=query&prop=info&intoken=edit&titles='+title,function(data){
			        var token;
			        for (var i in data.query.pages) {
				        token = data.query.pages[i].edittoken;
			        };
			        var args = {
				        action:'edit',
				        title: title,
				        text: box.$text.val(),
				        summary: 'from css editor',
				        token: token,
				        format: 'json'
			        };
			        $.post('/w/api.php', args, function(data) {
			          if (data.error) {
			          } else {
			            box.$save.showtip(data.edit.result);
                  box.$tab.text(box.name);
                  box.$revert.attr('disabled','disabled')
                  box.$save && box.$save.attr('disabled','disabled');
                }
			        });
		        },'json');
	        },'json');
        })
        .appendTo(box.$bar);
      };
      me.setupBox(doc,box);
    });  
    return box;
  }
})
};
if (self===top) jQuery(CustomTools);

