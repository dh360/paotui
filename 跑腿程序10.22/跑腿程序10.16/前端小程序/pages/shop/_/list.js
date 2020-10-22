var t = getApp(), e = t.require("utils/util.js"), api = t.require("utils/api.js"), i = {}, o = [], r = -1;

Page({
  data: {
    screenWidth: t.globalData.systemInfo.windowWidth,
    screenHeight: t.globalData.systemInfo.windowHeight,
    cateId:0,
    keyword:'',
    value:'',

    typeIndex: r,
    lastPage: !1,
    model: o
  },


  bindinput: function (t) {
    var a = t.detail.value;
    this.setData({
      value: a
    });
  },

  search: function (t) {
    var a = this.data.value, e = this;

    
    var s = {
      keyword:a,
      id: this.data.cateId
    };

    console.log('this.data', this.data),
    console.log('s',s),
    this.loadData(s);
  },


  onLoad: function (t) {

    console.log('onLoad',t),

    this.setData({
      cateId:t.id,
      index: t.cate_id
    }),this.loadData(t);
  },

  onReady: function () { },
  onShow: function () { 
  },
  onHide: function () { },
  onUnload: function () { },
  
  
	onPullDownRefresh: function() {
		n = 0, this.loadData(null, wx.stopPullDownRefresh, wx.stopPullDownRefresh);
	},
	onReachBottom: function() {
		this.data.lastPage || this.loadData({
			pageIndex: ++n
		});
	},
	
	
  onShareAppMessage: function () {
    t.onAppShareAppMessage('');
  },
 
 
  onFormSubmit: function (t) {
   	console.log('onFormSubmit',t);
   
  },
  
  
  loadData: function(a, n, r) {
        var s = this;
        var homeData = wx.getStorageSync("homeData");
        var school_id = homeData.school_id;
        console.log('school_id',school_id);
        console.log('a',a);
        
      api.shopList(a,function(t) {
		      console.log('==t.list==',t.list);	
          if (a && a.pageIndex) for (var r in t) o.push(t[r]); else o = t.list;
            s.setData({
                lastPage: t.length < e.pageSize,
                model: o,
                errandTypes: t.cates
            }), n && n();
        }, function() {
            r && r();
        });
    },
  
  
});